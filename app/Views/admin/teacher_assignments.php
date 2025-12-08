<?= $this->extend('template') ?>
<?= $this->section('styles') ?><?= $this->endSection() ?>

<?= $this->section('body_class') ?>page-teacher-assignments<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Teacher Assignment Management</h2>
        <a href="<?= site_url('/dashboard') ?>" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Create Assignment Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-chalkboard-teacher"></i> Assign Teacher to Course</h5>
        </div>
        <div class="card-body">
            <form method="post" action="<?= site_url('/admin/teacher-assignments/create') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="teacher_id" class="form-label">Teacher <span class="text-danger">*</span></label>
                        <select class="form-select" id="teacher_id" name="teacher_id" required>
                            <option value="">Select Teacher</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= $teacher['id'] ?>"><?= esc($teacher['name']) ?> (<?= esc($teacher['email']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="course_id" class="form-label">Course <span class="text-danger">*</span></label>
                        <select class="form-select" id="course_id" name="course_id" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['id'] ?>" 
                                    data-school-year="<?= $course['school_year_id'] ?>"
                                    data-semester="<?= $course['semester'] ?>"
                                    data-term="<?= $course['term'] ?>">
                                    <?= esc($course['control_number'] ?? 'N/A') ?> - <?= esc($course['title']) ?> (<?= $course['units'] ?? '0' ?> units) - <?= esc($course['school_year'] ?? 'N/A') ?> - Semester <?= $course['semester'] ?>, Term <?= $course['term'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Course includes School Year, Semester, and Term information</small>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary" id="assignTeacherBtn">
                            <i class="fas fa-save"></i> Assign Teacher
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Assignments List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list"></i> All Teacher Assignments</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Teacher</th>
                            <th>Course</th>
                            <th>Control Number</th>
                            <th>Units</th>
                            <th>School Year</th>
                            <th>Semester</th>
                            <th>Term</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($assignments)): ?>
                            <?php foreach ($assignments as $assignment): ?>
                                <tr>
                                    <td><?= $assignment['id'] ?></td>
                                    <td><?= esc($assignment['teacher_name']) ?></td>
                                    <td><?= esc($assignment['course_title']) ?></td>
                                    <td><strong><?= esc($assignment['control_number'] ?? 'N/A') ?></strong></td>
                                    <td><span class="badge bg-info"><?= $assignment['units'] ?? '0' ?> units</span></td>
                                    <td><?= esc($assignment['school_year']) ?></td>
                                    <td>Semester <?= $assignment['semester'] ?></td>
                                    <td>Term <?= $assignment['term'] ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick="editAssignment(<?= htmlspecialchars(json_encode($assignment)) ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="post" action="<?= site_url('/admin/teacher-assignments/delete') ?>" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $assignment['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center text-muted">No assignments found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Deleted Assignments Section -->
    <?php if (!empty($deleted_assignments)): ?>
    <div class="card mt-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fas fa-trash"></i> Deleted Teacher Assignments</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Teacher</th>
                            <th>Course</th>
                            <th>CN</th>
                            <th>Units</th>
                            <th>School Year</th>
                            <th>Semester</th>
                            <th>Term</th>
                            <th>Deleted At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deleted_assignments as $assignment): ?>
                            <tr class="table-secondary">
                                <td><?= $assignment['id'] ?></td>
                                <td><?= esc($assignment['teacher_name']) ?></td>
                                <td><?= esc($assignment['course_title']) ?></td>
                                <td><strong><?= esc($assignment['control_number'] ?? 'N/A') ?></strong></td>
                                <td><span class="badge bg-info"><?= $assignment['units'] ?? '0' ?> units</span></td>
                                <td><?= esc($assignment['school_year']) ?></td>
                                <td>Semester <?= $assignment['semester'] ?></td>
                                <td>Term <?= $assignment['term'] ?></td>
                                <td><?= $assignment['deleted_at'] ? date('M d, Y H:i', strtotime($assignment['deleted_at'])) : 'N/A' ?></td>
                                <td>
                                    <form method="post" action="<?= site_url('/admin/teacher-assignments/restore') ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to restore this assignment?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $assignment['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="fas fa-undo"></i> Restore
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Edit Assignment Modal -->
<div class="modal fade" id="editAssignmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= site_url('/admin/teacher-assignments/update') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="edit_assignment_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Teacher Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_teacher_id" class="form-label">Teacher</label>
                        <select class="form-select" id="edit_teacher_id" name="teacher_id" required>
                            <option value="">Select Teacher</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= $teacher['id'] ?>"><?= esc($teacher['name']) ?> (<?= esc($teacher['email']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_course_id" class="form-label">Course</label>
                        <select class="form-select" id="edit_course_id" name="course_id" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['id'] ?>" 
                                    data-school-year="<?= $course['school_year_id'] ?>"
                                    data-semester="<?= $course['semester'] ?>"
                                    data-term="<?= $course['term'] ?>">
                                    <?= esc($course['control_number'] ?? 'N/A') ?> - <?= esc($course['title']) ?> (<?= $course['units'] ?? '0' ?> units) - <?= esc($course['school_year'] ?? 'N/A') ?> - Semester <?= $course['semester'] ?>, Term <?= $course['term'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Update Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Store original values for validation
let originalTeacherId = null;
let originalCourseId = null;

function editAssignment(assignment) {
    console.log('Editing assignment:', assignment);
    console.log('Assignment ID:', assignment.id);
    console.log('Teacher ID:', assignment.teacher_id);
    console.log('Course ID:', assignment.course_id);
    
    if (!assignment.id) {
        alert('Error: Assignment ID is missing!');
        console.error('Assignment data:', assignment);
        return;
    }
    
    // Store original values before setting form values
    originalTeacherId = assignment.teacher_id || '';
    originalCourseId = assignment.course_id || '';
    
    document.getElementById('edit_assignment_id').value = assignment.id || '';
    document.getElementById('edit_teacher_id').value = assignment.teacher_id || '';
    document.getElementById('edit_course_id').value = assignment.course_id || '';
    
    console.log('Form values set - ID:', document.getElementById('edit_assignment_id').value);
    console.log('Form values set - Teacher:', document.getElementById('edit_teacher_id').value);
    console.log('Form values set - Course:', document.getElementById('edit_course_id').value);
    console.log('Original values stored - Teacher:', originalTeacherId, 'Course:', originalCourseId);
    
    new bootstrap.Modal(document.getElementById('editAssignmentModal')).show();
}

// Prevent double submission on teacher assignment forms
document.addEventListener('DOMContentLoaded', function() {
    // Handle create form
    const assignForm = document.querySelector('form[action*="/admin/teacher-assignments/create"]');
    const assignBtn = document.getElementById('assignTeacherBtn');
    
    if (assignForm && assignBtn) {
        assignForm.addEventListener('submit', function(e) {
            // Disable button and change text to prevent double clicks
            assignBtn.disabled = true;
            assignBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Assigning...';
            // Let the form submit normally (don't prevent default)
        });
    }
    
    // Handle update form
    const updateForm = document.querySelector('form[action*="/admin/teacher-assignments/update"]');
    if (updateForm) {
        updateForm.addEventListener('submit', function(e) {
            const id = document.getElementById('edit_assignment_id').value;
            const teacherId = document.getElementById('edit_teacher_id').value;
            const courseId = document.getElementById('edit_course_id').value;
            
            console.log('Submitting update form:', {id: id, teacherId: teacherId, courseId: courseId});
            console.log('Original values:', {teacherId: originalTeacherId, courseId: originalCourseId});
            
            if (!id || !teacherId || !courseId) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            // Validation: If course is changed, teacher must also be changed
            if (originalCourseId && originalCourseId !== courseId && originalTeacherId === teacherId) {
                e.preventDefault();
                alert('When changing the course, you must also assign a different teacher. The same teacher cannot be assigned to a different course in this assignment.');
                return false;
            }
            
            // Disable submit button to prevent double submission
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            }
        });
    }
});
</script>

<?= $this->endSection() ?>

