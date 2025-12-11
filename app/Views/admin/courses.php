<?= $this->extend('template') ?>
<?= $this->section('styles') ?><?= $this->endSection() ?>

<?= $this->section('body_class') ?>page-courses<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Course Management</h2>
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

    <!-- Create Course Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-plus"></i> Create New Course</h5>
        </div>
        <div class="card-body">
            <form method="post" action="<?= site_url('/admin/courses/create') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="title" class="form-label">Course Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= function_exists('set_value') ? set_value('title') : (old('title') ?? '') ?>" required minlength="3" maxlength="30" pattern="^[a-zA-Z0-9\s]+$" placeholder="Enter course title" title="Only letters, numbers, and spaces are allowed">
                        <small class="form-text text-muted">Minimum 3 characters, maximum 30 characters. Only letters, numbers, and spaces allowed (no special characters).</small>
                        <?php if (session()->getFlashdata('validation') && isset(session()->getFlashdata('validation')['title'])): ?>
                            <div class="text-danger small"><?= session()->getFlashdata('validation')['title'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label for="control_number_year" class="form-label">Control Number (CN) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light" style="user-select: none; cursor: default;">CN-</span>
                            <input type="text" class="form-control" id="control_number_year" name="control_number_year" maxlength="4" placeholder="2024" title="Enter 4-digit year only" required>
                            <input type="hidden" id="control_number" name="control_number" value="">
                        </div>
                        <small class="form-text text-muted">Enter 4-digit year only (e.g., 2024). CN- prefix is automatic.</small>
                        <div class="text-danger small mt-1 cn-error-msg" style="display: none;"></div>
                        <?php if (session()->getFlashdata('validation') && isset(session()->getFlashdata('validation')['control_number'])): ?>
                            <div class="text-danger small"><?= session()->getFlashdata('validation')['control_number'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label for="units" class="form-label">Units <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="units" name="units" value="<?= function_exists('set_value') ? set_value('units') : (old('units') ?? '0') ?>" required min="0" max="5" step="1">
                        <small class="form-text text-muted">Maximum 5 units only</small>
                        <div class="text-danger small mt-1 units-error-msg" style="display: none;"></div>
                        <?php if (session()->getFlashdata('validation') && isset(session()->getFlashdata('validation')['units'])): ?>
                            <div class="text-danger small"><?= session()->getFlashdata('validation')['units'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label for="time" class="form-label">Time</label>
                        <select class="form-select" id="time" name="time">
                            <option value="">Select Time</option>
                            <option value="7:00 AM - 8:00 AM" <?= (function_exists('set_value') ? set_value('time') : (old('time') ?? '')) == '7:00 AM - 8:00 AM' ? 'selected' : '' ?>>7:00 AM - 8:00 AM</option>
                            <option value="8:00 AM - 9:00 AM" <?= (function_exists('set_value') ? set_value('time') : (old('time') ?? '')) == '8:00 AM - 9:00 AM' ? 'selected' : '' ?>>8:00 AM - 9:00 AM</option>
                            <option value="9:00 AM - 10:00 AM" <?= (function_exists('set_value') ? set_value('time') : (old('time') ?? '')) == '9:00 AM - 10:00 AM' ? 'selected' : '' ?>>9:00 AM - 10:00 AM</option>
                            <option value="10:00 AM - 11:00 AM" <?= (function_exists('set_value') ? set_value('time') : (old('time') ?? '')) == '10:00 AM - 11:00 AM' ? 'selected' : '' ?>>10:00 AM - 11:00 AM</option>
                            <option value="11:00 AM - 12:00 PM" <?= (function_exists('set_value') ? set_value('time') : (old('time') ?? '')) == '11:00 AM - 12:00 PM' ? 'selected' : '' ?>>11:00 AM - 12:00 PM</option>
                            <option value="12:00 PM - 1:00 PM" <?= (function_exists('set_value') ? set_value('time') : (old('time') ?? '')) == '12:00 PM - 1:00 PM' ? 'selected' : '' ?>>12:00 PM - 1:00 PM</option>
                            <option value="1:00 PM - 2:00 PM" <?= (function_exists('set_value') ? set_value('time') : (old('time') ?? '')) == '1:00 PM - 2:00 PM' ? 'selected' : '' ?>>1:00 PM - 2:00 PM</option>
                            <option value="2:00 PM - 3:00 PM" <?= (function_exists('set_value') ? set_value('time') : (old('time') ?? '')) == '2:00 PM - 3:00 PM' ? 'selected' : '' ?>>2:00 PM - 3:00 PM</option>
                            <option value="3:00 PM - 4:00 PM" <?= (function_exists('set_value') ? set_value('time') : (old('time') ?? '')) == '3:00 PM - 4:00 PM' ? 'selected' : '' ?>>3:00 PM - 4:00 PM</option>
                            <option value="4:00 PM - 5:00 PM" <?= (function_exists('set_value') ? set_value('time') : (old('time') ?? '')) == '4:00 PM - 5:00 PM' ? 'selected' : '' ?>>4:00 PM - 5:00 PM</option>
                            <option value="5:00 PM - 6:00 PM" <?= (function_exists('set_value') ? set_value('time') : (old('time') ?? '')) == '5:00 PM - 6:00 PM' ? 'selected' : '' ?>>5:00 PM - 6:00 PM</option>
                            <option value="6:00 PM - 7:00 PM" <?= (function_exists('set_value') ? set_value('time') : (old('time') ?? '')) == '6:00 PM - 7:00 PM' ? 'selected' : '' ?>>6:00 PM - 7:00 PM</option>
                            <option value="7:00 PM - 8:00 PM" <?= (function_exists('set_value') ? set_value('time') : (old('time') ?? '')) == '7:00 PM - 8:00 PM' ? 'selected' : '' ?>>7:00 PM - 8:00 PM</option>
                        </select>
                        <small class="form-text text-muted">Select course schedule time</small>
                    </div>
                    <div class="col-md-12">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?= function_exists('set_value') ? set_value('description') : (old('description') ?? '') ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label for="school_year_id" class="form-label">School Year <span class="text-danger">*</span></label>
                        <select class="form-select" id="school_year_id" name="school_year_id" required>
                            <option value="">Select School Year</option>
                            <?php foreach ($school_years as $sy): ?>
                                <option value="<?= $sy['id'] ?>" 
                                    data-active="<?= $sy['is_active'] ? '1' : '0' ?>"
                                    <?= (function_exists('set_value') ? set_value('school_year_id') : (old('school_year_id') ?? '')) == $sy['id'] ? 'selected' : '' ?>
                                    <?= !$sy['is_active'] ? 'class="text-muted"' : '' ?>>
                                    <?= esc($sy['school_year']) ?><?= $sy['is_active'] ? ' (Active)' : ' (Inactive)' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="text-danger small mt-1 school-year-error-msg" style="display: none;"></div>
                        <?php if (session()->getFlashdata('validation') && isset(session()->getFlashdata('validation')['school_year_id'])): ?>
                            <div class="text-danger small"><?= session()->getFlashdata('validation')['school_year_id'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label for="semester" class="form-label">Semester <span class="text-danger">*</span></label>
                        <select class="form-select" id="semester" name="semester" required>
                            <option value="">Select Semester</option>
                            <option value="1" <?= (function_exists('set_value') ? set_value('semester') : (old('semester') ?? '')) == '1' ? 'selected' : '' ?>>Semester 1</option>
                            <option value="2" <?= (function_exists('set_value') ? set_value('semester') : (old('semester') ?? '')) == '2' ? 'selected' : '' ?>>Semester 2</option>
                        </select>
                        <?php if (session()->getFlashdata('validation') && isset(session()->getFlashdata('validation')['semester'])): ?>
                            <div class="text-danger small"><?= session()->getFlashdata('validation')['semester'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label for="term" class="form-label">Term <span class="text-danger">*</span></label>
                        <select class="form-select" id="term" name="term" required>
                            <option value="">Select Term</option>
                            <option value="1" <?= (function_exists('set_value') ? set_value('term') : (old('term') ?? '')) == '1' ? 'selected' : '' ?>>Term 1</option>
                            <option value="2" <?= (function_exists('set_value') ? set_value('term') : (old('term') ?? '')) == '2' ? 'selected' : '' ?>>Term 2</option>
                        </select>
                        <?php if (session()->getFlashdata('validation') && isset(session()->getFlashdata('validation')['term'])): ?>
                            <div class="text-danger small"><?= session()->getFlashdata('validation')['term'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary" id="createCourseBtn">
                            <i class="fas fa-save"></i> Create Course
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Courses List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list"></i> All Courses</h5>
        </div>
        <div class="card-body">
            <!-- Search Form -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <form id="searchForm" class="d-flex">
                        <div class="input-group">
                            <input type="text" id="searchInput" class="form-control" placeholder="Search courses..." name="search_term">
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Control Number</th>
                            <th>Title</th>
                            <th>Units</th>
                            <th>Time</th>
                            <th>Instructor</th>
                            <th>School Year</th>
                            <th>Semester</th>
                            <th>Term</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="coursesContainer">
                        <?php if (!empty($courses)): ?>
                            <?php foreach ($courses as $course): ?>
                                <tr class="course-row" data-title="<?= strtolower(esc($course['title'])) ?>" data-description="<?= strtolower(esc($course['description'] ?? '')) ?>">
                                    <td><?= $course['id'] ?></td>
                                    <td><strong><?= esc($course['control_number'] ?? 'N/A') ?></strong></td>
                                    <td><?= esc($course['title']) ?></td>
                                    <td><span class="badge bg-info"><?= $course['units'] ?? '0' ?> units</span></td>
                                    <td><?= esc($course['time'] ?? 'N/A') ?></td>
                                    <td><?= esc($course['instructor_name'] ?? 'Not Assigned') ?></td>
                                    <td><?= esc($course['school_year'] ?? 'N/A') ?></td>
                                    <td>Semester <?= $course['semester'] ?? 'N/A' ?></td>
                                    <td>Term <?= $course['term'] ?? 'N/A' ?></td>
                                    <td>
                                        <a href="<?= site_url('/materials/upload/' . $course['id']) ?>" class="btn btn-sm btn-primary" title="Manage Materials">
                                            <i class="fas fa-file-alt"></i>
                                        </a>
                                        <button class="btn btn-sm btn-warning" onclick="editCourse(<?= htmlspecialchars(json_encode($course)) ?>)" title="Edit Course">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="post" action="<?= site_url('/admin/courses/delete') ?>" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $course['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete Course">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="10" class="text-center text-muted">No courses found</td></tr>
                            <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Deleted Courses Section -->
    <?php if (!empty($deleted_courses)): ?>
    <div class="card mt-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fas fa-trash"></i> Deleted Courses</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>CN</th>
                            <th>Title</th>
                            <th>Units</th>
                            <th>School Year</th>
                            <th>Semester</th>
                            <th>Term</th>
                            <th>Deleted At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deleted_courses as $course): ?>
                            <tr class="table-secondary">
                                <td><?= $course['id'] ?></td>
                                <td><strong><?= esc($course['control_number'] ?? 'N/A') ?></strong></td>
                                <td><?= esc($course['title']) ?></td>
                                <td><span class="badge bg-info"><?= $course['units'] ?? '0' ?> units</span></td>
                                <td><?= esc($course['school_year'] ?? 'N/A') ?></td>
                                <td>Semester <?= $course['semester'] ?? 'N/A' ?></td>
                                <td>Term <?= $course['term'] ?? 'N/A' ?></td>
                                <td><?= $course['deleted_at'] ? date('M d, Y H:i', strtotime($course['deleted_at'])) : 'N/A' ?></td>
                                <td>
                                    <form method="post" action="<?= site_url('/admin/courses/restore') ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to restore this course?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $course['id'] ?>">
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

<!-- Edit Course Modal -->
<div class="modal fade" id="editCourseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= site_url('/admin/courses/update') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="edit_course_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="edit_title" name="title" required minlength="3" maxlength="30" pattern="^[a-zA-Z0-9\s]+$" placeholder="Enter course title" title="Only letters, numbers, and spaces are allowed">
                        <small class="form-text text-muted">Minimum 3 characters, maximum 30 characters. Only letters, numbers, and spaces allowed (no special characters).</small>
                    </div>
                    <div class="mb-3">
                        <label for="edit_control_number_year" class="form-label">Control Number (CN)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light" style="user-select: none; cursor: default;">CN-</span>
                            <input type="text" class="form-control" id="edit_control_number_year" name="control_number_year" maxlength="4" placeholder="2024" title="Enter 4-digit year only" required>
                            <input type="hidden" id="edit_control_number" name="control_number" value="">
                        </div>
                        <small class="form-text text-muted">Enter 4-digit year only (e.g., 2024). CN- prefix is automatic.</small>
                        <div class="text-danger small mt-1 cn-error-msg" style="display: none;"></div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_units" class="form-label">Units</label>
                        <input type="number" class="form-control" id="edit_units" name="units" required min="0" max="5" step="1">
                        <small class="form-text text-muted">Maximum 5 units only</small>
                        <div class="text-danger small mt-1 units-error-msg" style="display: none;"></div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_time" class="form-label">Time</label>
                        <select class="form-select" id="edit_time" name="time">
                            <option value="">Select Time</option>
                            <option value="7:00 AM - 8:00 AM">7:00 AM - 8:00 AM</option>
                            <option value="8:00 AM - 9:00 AM">8:00 AM - 9:00 AM</option>
                            <option value="9:00 AM - 10:00 AM">9:00 AM - 10:00 AM</option>
                            <option value="10:00 AM - 11:00 AM">10:00 AM - 11:00 AM</option>
                            <option value="11:00 AM - 12:00 PM">11:00 AM - 12:00 PM</option>
                            <option value="12:00 PM - 1:00 PM">12:00 PM - 1:00 PM</option>
                            <option value="1:00 PM - 2:00 PM">1:00 PM - 2:00 PM</option>
                            <option value="2:00 PM - 3:00 PM">2:00 PM - 3:00 PM</option>
                            <option value="3:00 PM - 4:00 PM">3:00 PM - 4:00 PM</option>
                            <option value="4:00 PM - 5:00 PM">4:00 PM - 5:00 PM</option>
                            <option value="5:00 PM - 6:00 PM">5:00 PM - 6:00 PM</option>
                            <option value="6:00 PM - 7:00 PM">6:00 PM - 7:00 PM</option>
                            <option value="7:00 PM - 8:00 PM">7:00 PM - 8:00 PM</option>
                        </select>
                        <small class="form-text text-muted">Select course schedule time</small>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_school_year_id" class="form-label">School Year</label>
                        <select class="form-select" id="edit_school_year_id" name="school_year_id" required>
                            <?php foreach ($school_years as $sy): ?>
                                <option value="<?= $sy['id'] ?>" 
                                    data-active="<?= $sy['is_active'] ? '1' : '0' ?>"
                                    <?= !$sy['is_active'] ? 'class="text-muted"' : '' ?>>
                                    <?= esc($sy['school_year']) ?><?= $sy['is_active'] ? ' (Active)' : ' (Inactive)' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="text-danger small mt-1 school-year-error-msg" style="display: none;"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="edit_semester" class="form-label">Semester</label>
                            <select class="form-select" id="edit_semester" name="semester" required>
                                <option value="1">Semester 1</option>
                                <option value="2">Semester 2</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_term" class="form-label">Term</label>
                            <select class="form-select" id="edit_term" name="term" required>
                                <option value="1">Term 1</option>
                                <option value="2">Term 2</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCourse(course) {
    document.getElementById('edit_course_id').value = course.id;
    document.getElementById('edit_title').value = course.title || '';
    
    // Extract year from control_number (e.g., CN-2024 -> 2024)
    const cnValue = course.control_number || '';
    const yearMatch = cnValue.match(/CN-(\d{4})/);
    if (yearMatch) {
        document.getElementById('edit_control_number_year').value = yearMatch[1];
    } else {
        document.getElementById('edit_control_number_year').value = '';
    }
    
    document.getElementById('edit_units').value = course.units || '0';
    // Set time value - handle null/undefined/empty
    const timeValue = course.time || '';
    document.getElementById('edit_time').value = timeValue;
    console.log('Setting time value:', timeValue, 'from course.time:', course.time);
    document.getElementById('edit_description').value = course.description || '';
    document.getElementById('edit_school_year_id').value = course.school_year_id || '';
    document.getElementById('edit_semester').value = course.semester || '';
    document.getElementById('edit_term').value = course.term || '';
    new bootstrap.Modal(document.getElementById('editCourseModal')).show();
}

// Prevent double submission on course creation form
document.addEventListener('DOMContentLoaded', function() {
    const createForm = document.querySelector('form[action*="/admin/courses/create"]');
    const createBtn = document.getElementById('createCourseBtn');
    
    if (createForm && createBtn) {
        createForm.addEventListener('submit', function(e) {
            // Ensure control_number is set before submission
            const yearInput = document.getElementById('control_number_year');
            const hiddenInput = document.getElementById('control_number');
            if (yearInput && hiddenInput && yearInput.value.length === 4) {
                hiddenInput.value = 'CN-' + yearInput.value;
            }
            
            // Disable button and change text to prevent double clicks
            createBtn.disabled = true;
            createBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
            // Let the form submit normally (don't prevent default)
        });
    }
    
    // Also handle edit form submission
    const editForm = document.querySelector('form[action*="/admin/courses/update"]');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            // Log form data before submission
            const formData = new FormData(editForm);
            console.log('Edit form submitting with data:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ':', value);
            }
            // Ensure control_number is set before submission
            const yearInput = document.getElementById('edit_control_number_year');
            const hiddenInput = document.getElementById('edit_control_number');
            if (yearInput && hiddenInput && yearInput.value.length === 4) {
                hiddenInput.value = 'CN-' + yearInput.value;
            }
        });
    }
    
    // Client-side validation for School Year (must be active)
    const schoolYearSelects = document.querySelectorAll('select[name="school_year_id"]');
    schoolYearSelects.forEach(function(select) {
        let errorMsg = select.parentElement.querySelector('.school-year-error-msg');
        if (!errorMsg) {
            errorMsg = document.createElement('div');
            errorMsg.className = 'text-danger small mt-1 school-year-error-msg';
            errorMsg.style.display = 'none';
            select.parentElement.appendChild(errorMsg);
        }
        
        select.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const isActive = selectedOption.getAttribute('data-active') === '1';
            
            if (this.value && !isActive) {
                const schoolYear = selectedOption.textContent.replace(' (Inactive)', '').replace(' (Active)', '');
                this.setCustomValidity('Selected school year is not active');
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
                errorMsg.textContent = 'ERROR: Cannot create course for School Year ' + schoolYear + '. Only active school years can be used for course creation. Please select an active school year.';
                errorMsg.style.display = 'block';
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
                errorMsg.style.display = 'none';
                if (this.value && isActive) {
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                }
            }
        });
    });
    
    // Client-side validation for Units (max 5)
    const unitsInputs = document.querySelectorAll('input[name="units"]');
    unitsInputs.forEach(function(input) {
        let errorMsg = input.parentElement.querySelector('.units-error-msg');
        if (!errorMsg) {
            errorMsg = document.createElement('div');
            errorMsg.className = 'text-danger small mt-1 units-error-msg';
            errorMsg.style.display = 'none';
            input.parentElement.appendChild(errorMsg);
        }
        
        input.addEventListener('input', function() {
            const value = parseInt(this.value) || 0;
            
            if (value > 5) {
                this.setCustomValidity('Units cannot exceed 5. Maximum is 5 units only.');
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
                errorMsg.textContent = 'ERROR: Units cannot exceed 5. Maximum is 5 units only.';
                errorMsg.style.display = 'block';
            } else if (value < 0) {
                this.setCustomValidity('Units cannot be negative.');
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
                errorMsg.textContent = 'ERROR: Units cannot be negative.';
                errorMsg.style.display = 'block';
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
                errorMsg.style.display = 'none';
                if (value >= 0 && value <= 5) {
                    this.classList.add('is-valid');
                }
            }
        });
        
        input.addEventListener('change', function() {
            const value = parseInt(this.value) || 0;
            if (value > 5) {
                this.value = 5;
                let errorMsg = this.parentElement.querySelector('.units-error-msg');
                if (!errorMsg) {
                    errorMsg = document.createElement('div');
                    errorMsg.className = 'text-danger small mt-1 units-error-msg';
                    this.parentElement.appendChild(errorMsg);
                }
                errorMsg.textContent = 'ERROR: Units cannot exceed 5. Maximum is 5 units only.';
                errorMsg.style.display = 'block';
                this.classList.add('is-invalid');
            }
        });
    });
    
    // Client-side validation for Course Title (no special characters)
    const titleInputs = document.querySelectorAll('input[name="title"]');
    titleInputs.forEach(function(input) {
        // Create error message element
        let errorMsg = input.parentElement.querySelector('.title-error-msg');
        if (!errorMsg) {
            errorMsg = document.createElement('div');
            errorMsg.className = 'text-danger small mt-1 title-error-msg';
            errorMsg.style.display = 'none';
            input.parentElement.appendChild(errorMsg);
        }
        
        input.addEventListener('input', function() {
            const value = this.value;
            const pattern = /^[a-zA-Z0-9\s]+$/;
            
            // Check for special characters
            if (value && !pattern.test(value)) {
                this.setCustomValidity('Special characters are not allowed in Course Title.');
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
                errorMsg.textContent = 'ERROR: Special characters are not allowed. Only letters, numbers, and spaces are permitted.';
                errorMsg.style.display = 'block';
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
                errorMsg.style.display = 'none';
                if (value.length >= 3 && value.length <= 150) {
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                }
            }
        });
        
        // Remove special characters as user types
        input.addEventListener('keypress', function(e) {
            const char = String.fromCharCode(e.which);
            const pattern = /^[a-zA-Z0-9\s]$/;
            if (!pattern.test(char)) {
                e.preventDefault();
                // Show error message
                let errorMsg = this.parentElement.querySelector('.title-error-msg');
                if (!errorMsg) {
                    errorMsg = document.createElement('div');
                    errorMsg.className = 'text-danger small mt-1 title-error-msg';
                    this.parentElement.appendChild(errorMsg);
                }
                errorMsg.textContent = 'ERROR: Special characters are not allowed. Only letters, numbers, and spaces are permitted.';
                errorMsg.style.display = 'block';
                this.classList.add('is-invalid');
            }
        });
    });
    
    // Client-side validation for Control Number format (CN- is automatic and locked)
    const controlNumberYearInputs = document.querySelectorAll('input[name="control_number_year"]');
    controlNumberYearInputs.forEach(function(input) {
        const hiddenInput = input.closest('.input-group').querySelector('input[name="control_number"]');
        
        // Find or create error message element
        let errorMsg = input.closest('.col-md-6, .mb-3').querySelector('.cn-error-msg');
        if (!errorMsg) {
            errorMsg = document.createElement('div');
            errorMsg.className = 'text-danger small mt-1 cn-error-msg';
            errorMsg.style.display = 'none';
            input.closest('.col-md-6, .mb-3').appendChild(errorMsg);
        }
        
        // Only allow numbers
        input.addEventListener('keypress', function(e) {
            const char = String.fromCharCode(e.which);
            if (!/^\d$/.test(char)) {
                e.preventDefault();
            }
        });
        
        // Validate and update hidden field
        let checkTimeout;
        input.addEventListener('input', function() {
            const yearValue = this.value.replace(/[^0-9]/g, ''); // Remove any non-digits
            this.value = yearValue; // Update input with cleaned value
            
            // Clear previous timeout
            if (checkTimeout) {
                clearTimeout(checkTimeout);
            }
            
            // Update hidden field with full CN-YYYY format
            if (hiddenInput) {
                hiddenInput.value = yearValue.length === 4 ? 'CN-' + yearValue : '';
            }
            
            // Validation
            if (yearValue.length === 0) {
                this.setCustomValidity('Please enter a 4-digit year');
                this.classList.remove('is-invalid', 'is-valid');
                errorMsg.style.display = 'none';
            } else if (yearValue.length > 4) {
                this.value = yearValue.substring(0, 4); // Limit to 4 digits
                this.setCustomValidity('Control Number must have exactly 4 digits');
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
                errorMsg.textContent = 'ERROR: Control Number must have exactly 4 digits only.';
                errorMsg.style.display = 'block';
                if (hiddenInput) {
                    hiddenInput.value = 'CN-' + this.value;
                }
            } else if (yearValue.length === 4) {
                // Check if CN already exists (debounce for 500ms)
                checkTimeout = setTimeout(() => {
                    const fullCN = 'CN-' + yearValue;
                    const isEditMode = input.id === 'edit_control_number_year';
                    const courseId = isEditMode ? document.getElementById('edit_course_id')?.value : null;
                    
                    // Check if CN exists
                    fetch(`<?= site_url('/admin/courses/check-cn') ?>?control_number=${encodeURIComponent(fullCN)}${courseId ? '&exclude_id=' + courseId : ''}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.exists) {
                                input.setCustomValidity('This Control Number already exists');
                                input.classList.add('is-invalid');
                                input.classList.remove('is-valid');
                                errorMsg.textContent = 'ERROR: Control Number ' + fullCN + ' already exists. ' + (data.course_title ? 'Used by: ' + data.course_title : '');
                                errorMsg.style.display = 'block';
                            } else {
                                input.setCustomValidity('');
                                input.classList.remove('is-invalid');
                                input.classList.add('is-valid');
                                errorMsg.style.display = 'none';
                            }
                        })
                        .catch(error => {
                            console.error('Error checking CN:', error);
                        });
                }, 500);
            } else {
                this.setCustomValidity('Please enter exactly 4 digits');
                this.classList.remove('is-invalid', 'is-valid');
                errorMsg.style.display = 'none';
            }
        });
        
        // Validate on blur
        input.addEventListener('blur', function() {
            const yearValue = this.value.replace(/[^0-9]/g, '');
            if (yearValue.length > 0 && yearValue.length !== 4) {
                this.setCustomValidity('Control Number must have exactly 4 digits');
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
                errorMsg.textContent = 'ERROR: Control Number must have exactly 4 digits only.';
                errorMsg.style.display = 'block';
            }
        });
    });
    
    // Client-side filtering with jQuery
    $(document).ready(function() {
        // Instant client-side filtering
        $('#searchInput').on('keyup', function() {
            const searchValue = $(this).val().toLowerCase();
            $('.course-row').each(function() {
                const title = $(this).data('title') || '';
                const description = $(this).data('description') || '';
                const text = title + ' ' + description;
                
                if (text.includes(searchValue)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
            
            // Show "No results" message if all rows are hidden
            const visibleRows = $('.course-row:visible').length;
            if (visibleRows === 0 && searchValue.length > 0) {
                if ($('#noResultsRow').length === 0) {
                    $('#coursesContainer').append('<tr id="noResultsRow"><td colspan="10" class="text-center text-muted">No courses found matching your search.</td></tr>');
                }
            } else {
                $('#noResultsRow').remove();
            }
        });
        
        // Server-side search with AJAX
        $('#searchForm').on('submit', function(e) {
            e.preventDefault();
            const searchTerm = $('#searchInput').val();
            
            $.get('<?= site_url('/courses/search') ?>', { search_term: searchTerm })
                .done(function(data) {
                    $('#coursesContainer').empty();
                    
                    if (data.length > 0) {
                        $.each(data, function(index, course) {
                            // Escape course data for use in HTML
                            const courseJson = JSON.stringify(course).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                            const row = $('<tr>')
                                .addClass('course-row')
                                .attr('data-title', (course.title || '').toLowerCase())
                                .attr('data-description', (course.description || '').toLowerCase())
                                .html(
                                    '<td>' + (course.id || '') + '</td>' +
                                    '<td><strong>' + (course.control_number || 'N/A') + '</strong></td>' +
                                    '<td>' + (course.title || '') + '</td>' +
                                    '<td><span class="badge bg-info">' + (course.units || '0') + ' units</span></td>' +
                                    '<td>' + (course.time || 'N/A') + '</td>' +
                                    '<td>' + (course.instructor_name || 'Not Assigned') + '</td>' +
                                    '<td>' + (course.school_year || 'N/A') + '</td>' +
                                    '<td>Semester ' + (course.semester || 'N/A') + '</td>' +
                                    '<td>Term ' + (course.term || 'N/A') + '</td>' +
                                    '<td>' +
                                    '<a href="<?= site_url('/materials/upload') ?>/' + course.id + '" class="btn btn-sm btn-primary" title="Manage Materials">' +
                                    '<i class="fas fa-file-alt"></i></a> ' +
                                    '<button class="btn btn-sm btn-warning" onclick="editCourse(' + courseJson + ')" title="Edit Course">' +
                                    '<i class="fas fa-edit"></i></button> ' +
                                    '<form method="post" action="<?= site_url('/admin/courses/delete') ?>" class="d-inline" onsubmit="return confirm(\'Are you sure?\')">' +
                                    '<?= csrf_field() ?>' +
                                    '<input type="hidden" name="id" value="' + course.id + '">' +
                                    '<button type="submit" class="btn btn-sm btn-danger" title="Delete Course">' +
                                    '<i class="fas fa-trash"></i></button>' +
                                    '</form>' +
                                    '</td>'
                                );
                            $('#coursesContainer').append(row);
                        });
                    } else {
                        $('#coursesContainer').html('<tr><td colspan="10" class="text-center text-muted">No courses found matching your search.</td></tr>');
                    }
                })
                .fail(function() {
                    $('#coursesContainer').html('<tr><td colspan="10" class="text-center text-danger">Error loading search results. Please try again.</td></tr>');
                });
        });
    });
});
</script>

<?= $this->endSection() ?>

