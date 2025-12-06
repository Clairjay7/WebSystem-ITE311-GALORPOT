<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WebSystem</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet" referrerpolicy="no-referrer" />
  <link href="https://unpkg.com/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
  <?= $this->renderSection('styles') ?>
</head>
<body class="<?= $this->renderSection('body_class') ?>">
<?php $uri = service('uri'); ?>
<?= $this->include('templates/header') ?>

<?= $this->renderSection('content') ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js" referrerpolicy="no-referrer"></script>
</body>
</html>