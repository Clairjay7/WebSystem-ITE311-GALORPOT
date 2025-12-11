<?= $this->extend('template') ?>
<?= $this->section('styles') ?><?= $this->endSection() ?>

<?= $this->section('body_class') ?>page-materials-upload<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-upload"></i> Upload Materials - <?= esc($course['title']) ?></h2>
        <?php
        $role = strtolower((string) session()->get('role'));
        $backUrl = ($role === 'admin') 
            ? site_url('/admin/courses') 
            : site_url('/instructor/course/' . $course['id']);
        ?>
        <a href="<?= $backUrl ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to <?= ($role === 'admin') ? 'Course Management' : 'Course' ?>
        </a>
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

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cloud-upload-alt"></i> Upload New Material</h5>
                </div>
                <div class="card-body">
                    <?= form_open_multipart('/materials/upload/' . $course['id']) ?>
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="material_file" class="form-label">Select File</label>
                            <input type="file" class="form-control <?= (isset($validation) && $validation->hasError('material_file')) ? 'is-invalid' : '' ?>" 
                                   id="material_file" name="material_file" 
                                   accept=".pdf,.ppt,.pptx" required>
                            <?php if (isset($validation) && $validation->hasError('material_file')): ?>
                                <div class="invalid-feedback">
                                    <?= $validation->getError('material_file') ?>
                                </div>
                            <?php endif; ?>
                            <small class="form-text text-muted">
                                <strong>Allowed file types:</strong> PDF (.pdf), PPT (.ppt), PPTX (.pptx) only<br>
                                <strong>Maximum file size:</strong> 10MB<br>
                                <strong>File name:</strong> Duplicate file names are not allowed in the same course<br>
                                <span class="text-danger">Other file types will be rejected.</span>
                            </small>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload Material
                        </button>
                    <?= form_close() ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-file"></i> Course Materials</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($materials)): ?>
                        <p class="text-muted">No materials uploaded yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Uploaded Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($materials as $material): ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-file"></i> <?= esc($material['file_name']) ?>
                                            </td>
                                            <td>
                                                <?= date('M d, Y H:i', strtotime($material['created_at'])) ?>
                                            </td>
                                            <td>
                                                <a href="<?= site_url('/materials/download/' . $material['id']) ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                                <a href="<?= site_url('/materials/delete/' . $material['id']) ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Are you sure you want to delete this material?');">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Deleted Materials Section -->
    <?php if (isset($deleted_materials) && !empty($deleted_materials)): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-secondary">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-trash"></i> Deleted Materials</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Uploaded Date</th>
                                        <th>Deleted Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($deleted_materials as $material): ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-file"></i> <?= esc($material['file_name']) ?>
                                            </td>
                                            <td>
                                                <?= date('M d, Y H:i', strtotime($material['created_at'])) ?>
                                            </td>
                                            <td>
                                                <?= isset($material['deleted_at']) ? date('M d, Y H:i', strtotime($material['deleted_at'])) : 'N/A' ?>
                                            </td>
                                            <td>
                                                <a href="<?= site_url('/materials/restore/' . $material['id']) ?>" 
                                                   class="btn btn-sm btn-success"
                                                   onclick="return confirm('Are you sure you want to restore this material?');">
                                                    <i class="fas fa-undo"></i> Restore
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>

