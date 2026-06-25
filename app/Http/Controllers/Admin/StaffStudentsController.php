<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\ClassGroup;
use App\Models\ClassGroupStudent;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffStudentsController extends Controller
{
    use InteractsWithAdminSession;

    public function index(Request $request): View
    {
        $user = $this->adminUser();
        if (! $user || (! $user->isSuperAdmin() && $user->role !== User::ROLE_COORDINATOR)) {
            abort(403);
        }

        $groupIds = $user->classGroupIds();
        $search = trim((string) $request->input('search', ''));
        $classGroupId = $request->input('class_group_id');
        $institutionId = $user->isSuperAdmin() ? $request->input('institution_id') : null;

        $query = ClassGroupStudent::query()
            ->with([
                'classGroup:id,name,examiner_id',
                'classGroup.examiner:id,name,username,institution_id',
                'classGroup.examiner.institution:id,name,region',
                'studentAccount:id,index_number,phone_contact,student_name',
            ])
            ->whereIn('class_group_id', $groupIds)
            ->orderBy('index_number');

        if ($classGroupId) {
            $query->where('class_group_id', $classGroupId);
        }

        if ($institutionId && $user->isSuperAdmin()) {
            $query->whereHas('classGroup.examiner', fn ($q) => $q->where('institution_id', $institutionId));
        }

        if ($search !== '') {
            $term = '%' . preg_replace('/%/', '\\%', $search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('index_number', 'like', $term)
                    ->orWhere('student_name', 'like', $term)
                    ->orWhereHas('studentAccount', function ($q2) use ($term) {
                        $q2->where('phone_contact', 'like', $term)
                            ->orWhere('student_name', 'like', $term);
                    });
            });
        }

        $students = $query->paginate(40)->withQueryString();

        $classGroups = ClassGroup::query()
            ->whereIn('id', $groupIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $institutions = $user->isSuperAdmin()
            ? Institution::orderBy('name')->get(['id', 'name', 'region'])
            : collect();

        $isSuperAdmin = $user->isSuperAdmin();

        return view('admin.students.staff-index', compact(
            'students',
            'classGroups',
            'institutions',
            'search',
            'isSuperAdmin',
        ));
    }
}
