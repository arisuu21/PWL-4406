<pre><?= print_r(session()->get(), true) ?></pre>

<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="pagetitle">
  <h1>Profil Pengguna</h1>
</div>

<section class="section">
  <div class="card">
    <div class="card-body">

      <h5 class="card-title">Profile</h5>

      <table class="table">
        <tr>
          <td><b>Username</b></td>
          <td><?= session()->get('username') ?></td>
        </tr>
        <tr>
          <td><b>Role</b></td>
          <td>
            <span class="badge bg-danger">
              <?= session()->get('role') ?>
            </span>
          </td>
        </tr>
        <tr>
          <td><b>Email</b></td>
          <td><?= session()->get('email') ?></td>
        </tr>
        <tr>
          <td><b>Login Time</b></td>
          <td><?= session()->get('login_time') ?></td>
        </tr>
        <tr>
          <td><b>Status</b></td>
          <td>
            <?php if(session()->get('isLoggedIn')): ?>
              <span class="badge bg-success">Sudah Login</span>
            <?php else: ?>
              <span class="badge bg-secondary">Belum Login</span>
            <?php endif; ?>
          </td>
        </tr>
      </table>

    </div>
  </div>
</section>

<?= $this->endSection() ?>