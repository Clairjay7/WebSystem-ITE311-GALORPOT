<?= $this->extend('template') ?>
<?= $this->section('styles') ?><?= $this->endSection() ?>

<?= $this->section('body_class') ?>page-academic<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Academic Structure Management</h2>
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
            <i class="fas fa-exclamation-triangle"></i> <strong>Error:</strong> 
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($migration_needed) && $migration_needed): ?>
        <div class="alert alert-danger">
            <h5><i class="fas fa-exclamation-triangle"></i> Database Setup Required</h5>
            <p><?= $error_message ?? 'Database migrations need to be run first.' ?></p>
            <p><strong>Steps to fix:</strong></p>
            <ol>
                <li>Open terminal/command prompt</li>
                <li>Navigate to: <code>F:\xammp\htdocs\ITE311-GALORPOT</code></li>
                <li>Run: <code>php spark migrate</code></li>
                <li>Refresh this page</li>
            </ol>
        </div>
    <?php endif; ?>

    <!-- Create School Year Form -->
    <div class="card mb-4 border-primary">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Create New School Year</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-warning mb-3">
                <strong><i class="fas fa-exclamation-triangle"></i> Required:</strong> You must provide dates for all terms:
                <ul class="mb-0 mt-2">
                    <li><strong>Semester 1</strong> - Term 1 and Term 2 dates</li>
                    <li><strong>Semester 2</strong> - Term 1 and Term 2 dates</li>
                </ul>
            </div>
            <form method="post" action="<?= site_url('/academic/school-year/create') ?>" id="createSchoolYearForm">
                <?= csrf_field() ?>
                <input type="hidden" name="form_submitted" value="1">
                
                <!-- School Year Input -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="school_year" class="form-label">School Year <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="school_year" name="school_year" 
                               placeholder="2024-2025" pattern="\d{4}-\d{4}" 
                               value="<?= function_exists('set_value') ? set_value('school_year') : (old('school_year') ?? '') ?>" required>
                        <div class="form-text">Format: YYYY-YYYY (e.g., 2024-2025)</div>
                    </div>
                </div>

                <!-- Semester 1 -->
                <div class="card border-info mb-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-book"></i> Semester 1 <span class="text-danger">*</span></h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Term 1 -->
                            <div class="col-md-6">
                                <div class="card border-secondary">
                                    <div class="card-header bg-secondary text-white py-2">
                                        <strong>Term 1 <span class="text-danger">*</span></strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <label for="sem1_term1_start" class="form-label small">Start Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" 
                                                   name="sem1_term1_start" id="sem1_term1_start" 
                                                   value="<?= function_exists('set_value') ? set_value('sem1_term1_start') : (old('sem1_term1_start') ?? '') ?>" required>
                                        </div>
                                        <div class="mb-2">
                                            <label for="sem1_term1_end" class="form-label small">End Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" 
                                                   name="sem1_term1_end" id="sem1_term1_end" 
                                                   value="<?= function_exists('set_value') ? set_value('sem1_term1_end') : (old('sem1_term1_end') ?? '') ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Term 2 -->
                            <div class="col-md-6">
                                <div class="card border-secondary">
                                    <div class="card-header bg-secondary text-white py-2">
                                        <strong>Term 2 <span class="text-danger">*</span></strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <label for="sem1_term2_start" class="form-label small">Start Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" 
                                                   name="sem1_term2_start" id="sem1_term2_start" 
                                                   value="<?= function_exists('set_value') ? set_value('sem1_term2_start') : (old('sem1_term2_start') ?? '') ?>" required>
                                        </div>
                                        <div class="mb-2">
                                            <label for="sem1_term2_end" class="form-label small">End Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" 
                                                   name="sem1_term2_end" id="sem1_term2_end" 
                                                   value="<?= function_exists('set_value') ? set_value('sem1_term2_end') : (old('sem1_term2_end') ?? '') ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Semester 2 -->
                <div class="card border-warning mb-3">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="fas fa-book"></i> Semester 2 <span class="text-danger">*</span></h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Term 1 -->
                            <div class="col-md-6">
                                <div class="card border-secondary">
                                    <div class="card-header bg-secondary text-white py-2">
                                        <strong>Term 1 <span class="text-danger">*</span></strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <label for="sem2_term1_start" class="form-label small">Start Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" 
                                                   name="sem2_term1_start" id="sem2_term1_start" 
                                                   value="<?= function_exists('set_value') ? set_value('sem2_term1_start') : (old('sem2_term1_start') ?? '') ?>" required>
                                        </div>
                                        <div class="mb-2">
                                            <label for="sem2_term1_end" class="form-label small">End Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" 
                                                   name="sem2_term1_end" id="sem2_term1_end" 
                                                   value="<?= function_exists('set_value') ? set_value('sem2_term1_end') : (old('sem2_term1_end') ?? '') ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Term 2 -->
                            <div class="col-md-6">
                                <div class="card border-secondary">
                                    <div class="card-header bg-secondary text-white py-2">
                                        <strong>Term 2 <span class="text-danger">*</span></strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <label for="sem2_term2_start" class="form-label small">Start Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" 
                                                   name="sem2_term2_start" id="sem2_term2_start" 
                                                   value="<?= function_exists('set_value') ? set_value('sem2_term2_start') : (old('sem2_term2_start') ?? '') ?>" required>
                                        </div>
                                        <div class="mb-2">
                                            <label for="sem2_term2_end" class="form-label small">End Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" 
                                                   name="sem2_term2_end" id="sem2_term2_end" 
                                                   value="<?= function_exists('set_value') ? set_value('sem2_term2_end') : (old('sem2_term2_end') ?? '') ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                        <i class="fas fa-save"></i> Create School Year with All Terms
                    </button>
                </div>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const form = document.getElementById('createSchoolYearForm');
                    const btn = document.getElementById('submitBtn');
                    
                    if (form && btn) {
                        form.addEventListener('submit', function(e) {
                            console.log('=== FORM SUBMIT EVENT TRIGGERED ===');
                            console.log('Form method:', form.method);
                            console.log('Form action:', form.action);
                            console.log('Form will submit as POST');
                            
                            // Only disable button, don't prevent form submission
                            if (!btn.disabled) {
                                btn.disabled = true;
                                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
                                console.log('Button disabled, form submitting...');
                            }
                            // CRITICAL: DO NOT prevent default - let form submit normally
                            // Form will submit as POST to the action URL
                        });
                    } else {
                        console.error('Form or button not found!');
                    }
                });
                </script>
            </form>
        </div>
    </div>

    <!-- School Years List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list"></i> School Years</h5>
        </div>
        <div class="card-body">
            <?php if (isset($migration_needed) && $migration_needed): ?>
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle"></i> Database Setup Required</h5>
                    <p><?= $error_message ?? 'Database migrations need to be run first.' ?></p>
                    <p><strong>Steps to fix:</strong></p>
                    <ol>
                        <li>Open terminal/command prompt</li>
                        <li>Navigate to: <code>F:\xammp\htdocs\ITE311-GALORPOT</code></li>
                        <li>Run: <code>php spark migrate</code></li>
                        <li>Refresh this page</li>
                    </ol>
                </div>
            <?php elseif (empty($school_years)): ?>
                <p class="text-muted">No school years created yet. Create one using the form above.</p>
            <?php else: ?>
                <?php foreach ($school_years as $sy): ?>
                    <div class="card mb-4 border-primary">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">
                                    <i class="fas fa-calendar-alt"></i> School Year: <?= esc($sy['school_year']) ?>
                                    <?php if ($sy['is_active']): ?>
                                        <span class="badge bg-success ms-2">Active</span>
                                    <?php else: ?>
                                        <form method="post" action="<?= site_url('/academic/school-year/set-active') ?>" class="d-inline ms-2">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $sy['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-light">Set as Active</button>
                                        </form>
                                    <?php endif; ?>
                                </h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($sy['semesters'])): ?>
                                <div class="row">
                                    <!-- Semester 1 -->
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100 border-info">
                                            <div class="card-header bg-info text-white">
                                                <h6 class="mb-0"><i class="fas fa-book"></i> Semester 1</h6>
                                            </div>
                                            <div class="card-body">
                                                <?php 
                                                $sem1 = null;
                                                foreach ($sy['semesters'] as $sem) {
                                                    if ($sem['semester_number'] == 1) {
                                                        $sem1 = $sem;
                                                        break;
                                                    }
                                                }
                                                ?>
                                                <?php if ($sem1 && !empty($sem1['terms'])): ?>
                                                    <div class="row g-2">
                                                        <!-- Term 1 -->
                                                        <div class="col-12">
                                                            <div class="card border-secondary">
                                                                <div class="card-header bg-secondary text-white py-2">
                                                                    <strong>Term 1</strong>
                                                                </div>
                                                                <div class="card-body p-2">
                                                                    <form method="post" action="<?= site_url('/academic/term/update-dates') ?>">
                                                                        <?= csrf_field() ?>
                                                                        <?php 
                                                                        $term1 = null;
                                                                        foreach ($sem1['terms'] as $term) {
                                                                            if ($term['term_number'] == 1) {
                                                                                $term1 = $term;
                                                                                break;
                                                                            }
                                                                        }
                                                                        ?>
                                                                        <input type="hidden" name="term_id" value="<?= $term1['id'] ?? '' ?>">
                                                                        <div class="mb-2">
                                                                            <label for="start_date_sem1_term1_<?= $term1['id'] ?? '' ?>" class="form-label small mb-1">Start Date</label>
                                                                            <input type="date" class="form-control form-control-sm" 
                                                                                   id="start_date_sem1_term1_<?= $term1['id'] ?? '' ?>"
                                                                                   name="start_date" value="<?= $term1['start_date'] ? esc($term1['start_date']) : '' ?>" required>
                                                                        </div>
                                                                        <div class="mb-2">
                                                                            <label for="end_date_sem1_term1_<?= $term1['id'] ?? '' ?>" class="form-label small mb-1">End Date</label>
                                                                            <input type="date" class="form-control form-control-sm" 
                                                                                   id="end_date_sem1_term1_<?= $term1['id'] ?? '' ?>"
                                                                                   name="end_date" value="<?= $term1['end_date'] ? esc($term1['end_date']) : '' ?>" required>
                                                                        </div>
                                                                        <button type="submit" class="btn btn-sm btn-primary w-100">
                                                                            <i class="fas fa-save"></i> Update Term 1
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <!-- Term 2 -->
                                                        <div class="col-12">
                                                            <div class="card border-secondary">
                                                                <div class="card-header bg-secondary text-white py-2">
                                                                    <strong>Term 2</strong>
                                                                </div>
                                                                <div class="card-body p-2">
                                                                    <form method="post" action="<?= site_url('/academic/term/update-dates') ?>">
                                                                        <?= csrf_field() ?>
                                                                        <?php 
                                                                        $term2 = null;
                                                                        foreach ($sem1['terms'] as $term) {
                                                                            if ($term['term_number'] == 2) {
                                                                                $term2 = $term;
                                                                                break;
                                                                            }
                                                                        }
                                                                        ?>
                                                                        <input type="hidden" name="term_id" value="<?= $term2['id'] ?? '' ?>">
                                                                        <div class="mb-2">
                                                                            <label for="start_date_sem1_term2_<?= $term2['id'] ?? '' ?>" class="form-label small mb-1">Start Date</label>
                                                                            <input type="date" class="form-control form-control-sm" 
                                                                                   id="start_date_sem1_term2_<?= $term2['id'] ?? '' ?>"
                                                                                   name="start_date" value="<?= $term2['start_date'] ? esc($term2['start_date']) : '' ?>" required>
                                                                        </div>
                                                                        <div class="mb-2">
                                                                            <label for="end_date_sem1_term2_<?= $term2['id'] ?? '' ?>" class="form-label small mb-1">End Date</label>
                                                                            <input type="date" class="form-control form-control-sm" 
                                                                                   id="end_date_sem1_term2_<?= $term2['id'] ?? '' ?>"
                                                                                   name="end_date" value="<?= $term2['end_date'] ? esc($term2['end_date']) : '' ?>" required>
                                                                        </div>
                                                                        <button type="submit" class="btn btn-sm btn-primary w-100">
                                                                            <i class="fas fa-save"></i> Update Term 2
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <p class="text-muted small">Terms will be created automatically.</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Semester 2 -->
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100 border-warning">
                                            <div class="card-header bg-warning text-dark">
                                                <h6 class="mb-0"><i class="fas fa-book"></i> Semester 2</h6>
                                            </div>
                                            <div class="card-body">
                                                <?php 
                                                $sem2 = null;
                                                foreach ($sy['semesters'] as $sem) {
                                                    if ($sem['semester_number'] == 2) {
                                                        $sem2 = $sem;
                                                        break;
                                                    }
                                                }
                                                ?>
                                                <?php if ($sem2 && !empty($sem2['terms'])): ?>
                                                    <div class="row g-2">
                                                        <!-- Term 1 -->
                                                        <div class="col-12">
                                                            <div class="card border-secondary">
                                                                <div class="card-header bg-secondary text-white py-2">
                                                                    <strong>Term 1</strong>
                                                                </div>
                                                                <div class="card-body p-2">
                                                                    <form method="post" action="<?= site_url('/academic/term/update-dates') ?>">
                                                                        <?= csrf_field() ?>
                                                                        <?php 
                                                                        $term1 = null;
                                                                        foreach ($sem2['terms'] as $term) {
                                                                            if ($term['term_number'] == 1) {
                                                                                $term1 = $term;
                                                                                break;
                                                                            }
                                                                        }
                                                                        ?>
                                                                        <input type="hidden" name="term_id" value="<?= $term1['id'] ?? '' ?>">
                                                                        <div class="mb-2">
                                                                            <label for="start_date_sem2_term1_<?= $term1['id'] ?? '' ?>" class="form-label small mb-1">Start Date</label>
                                                                            <input type="date" class="form-control form-control-sm" 
                                                                                   id="start_date_sem2_term1_<?= $term1['id'] ?? '' ?>"
                                                                                   name="start_date" value="<?= $term1['start_date'] ? esc($term1['start_date']) : '' ?>" required>
                                                                        </div>
                                                                        <div class="mb-2">
                                                                            <label for="end_date_sem2_term1_<?= $term1['id'] ?? '' ?>" class="form-label small mb-1">End Date</label>
                                                                            <input type="date" class="form-control form-control-sm" 
                                                                                   id="end_date_sem2_term1_<?= $term1['id'] ?? '' ?>"
                                                                                   name="end_date" value="<?= $term1['end_date'] ? esc($term1['end_date']) : '' ?>" required>
                                                                        </div>
                                                                        <button type="submit" class="btn btn-sm btn-primary w-100">
                                                                            <i class="fas fa-save"></i> Update Term 1
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <!-- Term 2 -->
                                                        <div class="col-12">
                                                            <div class="card border-secondary">
                                                                <div class="card-header bg-secondary text-white py-2">
                                                                    <strong>Term 2</strong>
                                                                </div>
                                                                <div class="card-body p-2">
                                                                    <form method="post" action="<?= site_url('/academic/term/update-dates') ?>">
                                                                        <?= csrf_field() ?>
                                                                        <?php 
                                                                        $term2 = null;
                                                                        foreach ($sem2['terms'] as $term) {
                                                                            if ($term['term_number'] == 2) {
                                                                                $term2 = $term;
                                                                                break;
                                                                            }
                                                                        }
                                                                        ?>
                                                                        <input type="hidden" name="term_id" value="<?= $term2['id'] ?? '' ?>">
                                                                        <div class="mb-2">
                                                                            <label for="start_date_sem2_term2_<?= $term2['id'] ?? '' ?>" class="form-label small mb-1">Start Date</label>
                                                                            <input type="date" class="form-control form-control-sm" 
                                                                                   id="start_date_sem2_term2_<?= $term2['id'] ?? '' ?>"
                                                                                   name="start_date" value="<?= $term2['start_date'] ? esc($term2['start_date']) : '' ?>" required>
                                                                        </div>
                                                                        <div class="mb-2">
                                                                            <label for="end_date_sem2_term2_<?= $term2['id'] ?? '' ?>" class="form-label small mb-1">End Date</label>
                                                                            <input type="date" class="form-control form-control-sm" 
                                                                                   id="end_date_sem2_term2_<?= $term2['id'] ?? '' ?>"
                                                                                   name="end_date" value="<?= $term2['end_date'] ? esc($term2['end_date']) : '' ?>" required>
                                                                        </div>
                                                                        <button type="submit" class="btn btn-sm btn-primary w-100">
                                                                            <i class="fas fa-save"></i> Update Term 2
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <p class="text-muted small">Terms will be created automatically.</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p class="text-muted small">Semesters will be created automatically when school year is created.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Current Academic Period Display -->
    <div class="card mt-4 border-success">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-check-circle"></i> Active Academic Period</h5>
        </div>
        <div class="card-body">
            <div id="currentPeriodDisplay">
                <p class="text-muted">Loading...</p>
            </div>
            <div class="alert alert-info mt-3 mb-0">
                <small><i class="fas fa-info-circle"></i> <strong>Note:</strong> When you create a new School Year, it will automatically become the Active Academic Period. The system will use this period to filter courses, enrollments, and assignments.</small>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fetch current academic period
    fetch('<?= site_url('/academic/current-period') ?>')
        .then(response => response.json())
        .then(data => {
            const display = document.getElementById('currentPeriodDisplay');
            if (data.status === 'success') {
                const termStartDate = new Date(data.data.term_start);
                const termEndDate = new Date(data.data.term_end);
                const formattedTermStart = termStartDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                const formattedTermEnd = termEndDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                
                // School Year Period (Start = Sem 1 Term 1 Start, End = Sem 2 Term 2 End)
                let schoolYearPeriodHtml = '';
                if (data.data.school_year_start && data.data.school_year_end) {
                    const syStartDate = new Date(data.data.school_year_start);
                    const syEndDate = new Date(data.data.school_year_end);
                    const formattedSYStart = syStartDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                    const formattedSYEnd = syEndDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                    
                    schoolYearPeriodHtml = `
                        <div class="alert alert-primary mt-3 mb-2">
                            <strong><i class="fas fa-calendar-alt"></i> School Year Period:</strong><br>
                            <strong>Start:</strong> ${formattedSYStart} (Semester 1 Term 1)<br>
                            <strong>End:</strong> ${formattedSYEnd} (Semester 2 Term 2)
                        </div>
                    `;
                }
                
                display.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-primary mb-2">
                                <div class="card-body">
                                    <h6 class="text-primary mb-2"><i class="fas fa-calendar-alt"></i> School Year</h6>
                                    <h4 class="mb-0">${data.data.school_year}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-info mb-2">
                                <div class="card-body">
                                    <h6 class="text-info mb-2"><i class="fas fa-book"></i> Semester</h6>
                                    <h4 class="mb-0">${data.data.semester}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-warning mb-2">
                                <div class="card-body">
                                    <h6 class="text-warning mb-2"><i class="fas fa-calendar-check"></i> Term</h6>
                                    <h4 class="mb-0">${data.data.term}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    ${schoolYearPeriodHtml}
                    <div class="alert alert-success mt-3 mb-0">
                        <strong><i class="fas fa-clock"></i> Current Term Period:</strong><br>
                        <strong>Start:</strong> ${formattedTermStart}<br>
                        <strong>End:</strong> ${formattedTermEnd}
                    </div>
                `;
            } else {
                display.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> <strong>${data.message}</strong>
                        <br><small>Create a School Year with term dates to set the active period.</small>
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('currentPeriodDisplay').innerHTML = 
                '<div class="alert alert-danger">Error loading current period.</div>';
        });
});
</script>

<?= $this->endSection() ?>

