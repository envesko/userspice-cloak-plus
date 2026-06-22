<?php
// Cloak+ - admin config page (Envesko). admin.php?view=plugins_config&plugin=cloakplus
// Gate by system-level cloaking permission (master account OR cloak_allowed), NOT just master -
// so nobody who couldn't cloak from core can reach this UI, and anyone who can, can.
if (!function_exists('cloakPlusCanUse') || !cloakPlusCanUse()) {
    die('You do not have permission to use Cloak+. You need cloaking permission (a master account, or "Is allowed to cloak" enabled on your user).');
}
global $db, $master_account;
$selfUrl = $us_url_root . 'users/admin.php?view=plugins_config&plugin=cloakplus';

if (Input::exists()) {
    if (!Token::check(Input::get('csrf'))) {
        usError('Invalid security token. Please refresh and try again.');
        Redirect::to($selfUrl);
    }
    $do = Input::get('do');

    if ($do === 'save_settings') {
        if ($db->query('SELECT id FROM cloakplus_config WHERE id = 1')->count() == 0) {
            $db->insert('cloakplus_config', ['id' => 1]);
        }
        $order = Input::get('list_order') === 'registered' ? 'registered' : 'active';
        $limit = (int) Input::get('list_limit');
        if ($limit < 1) {
            $limit = 25;
        }
        if ($limit > 200) {
            $limit = 200;
        }
        $db->update('cloakplus_config', 1, [
            'BannerEnabled' => Input::get('banner_enabled') ? 1 : 0,
            'ListOrder'     => $order,
            'ListLimit'     => $limit,
            'HideInactive'  => Input::get('hide_inactive') ? 1 : 0,
        ]);
        usSuccess('Cloak+ settings saved.');
        Redirect::to($selfUrl);
    }

    if ($do === 'cloak') {
        $res = CloakPlus_Cloak((int) Input::get('user_id')); // redirects on success
        usError('Could not cloak: ' . ($res['error'] ?? 'unknown'));
        Redirect::to($selfUrl);
    }
}

$cfg = cloakPlusConfig();
$bannerEnabled = cloakPlusBannerEnabled();
$listOrder = ($cfg && $cfg->ListOrder === 'registered') ? 'registered' : 'active';
$listLimit = $cfg ? max(1, min(200, (int) $cfg->ListLimit)) : 25;
$hideInactive = $cfg ? ((int) $cfg->HideInactive === 1) : false;

// Order/active clauses are built from whitelisted config values, never raw input.
$orderSql = $listOrder === 'registered' ? 'u.id DESC' : 'COALESCE(u.last_login, u.join_date) DESC';
$orderLabel = $listOrder === 'registered' ? 'newest registered first' : 'most recently active first';
$activeSql = $hideInactive ? ' AND u.active = 1 ' : '';

$q = trim((string) Input::get('q'));
$people = [];
try {
    if ($q !== '') {
        $like = '%' . $q . '%';
        $people = $db->query(
            "SELECT u.id, u.username, u.fname, u.lname, u.email FROM users u WHERE (u.username LIKE ? OR u.email LIKE ? OR u.fname LIKE ? OR u.lname LIKE ?) {$activeSql} ORDER BY {$orderSql} LIMIT 50",
            [$like, $like, $like, $like]
        )->results();
    } else {
        $people = $db->query("SELECT u.id, u.username, u.fname, u.lname, u.email FROM users u WHERE 1=1 {$activeSql} ORDER BY {$orderSql} LIMIT " . (int) $listLimit)->results();
    }
} catch (Exception $e) {
}
?>
<style>
  .cp-hero { background:linear-gradient(135deg,#2ea5cb 0%,#2edcb7 100%); color:#fff; border-radius:.6rem; padding:1rem 1.25rem; margin-bottom:1rem; }
  .cp-hero h2 { color:#fff; margin:0; font-weight:600; }
  .cp-hero p { margin:.25rem 0 0; opacity:.95; }
  .cp-btn { background:linear-gradient(135deg,#2ea5cb 0%,#2edcb7 100%); border:0; color:#fff; }
  .cp-btn:hover { filter:brightness(1.07); color:#fff; }
</style>

<div class="container-fluid">
  <div class="cp-hero">
    <h2><i class="fa fa-user-secret me-2"></i>Cloak+ <span style="font-weight:400;opacity:.85;font-size:1rem">by Envesko</span></h2>
    <p>Impersonate a user to see exactly what they see, then return with one click. A persistent banner keeps you oriented.</p>
  </div>

  <?php if (function_exists('isCloaked') && isCloaked()) { ?>
    <div class="alert alert-warning">You are currently cloaked. Use the banner at the bottom of the page to return to your account.</div>
  <?php } ?>

  <div class="row">
    <div class="col-12 col-lg-4 mb-4">
      <div class="card">
        <div class="card-header"><i class="fa fa-sliders me-1"></i> Settings</div>
        <div class="card-body">
          <form method="post" action="<?= safeReturn($selfUrl) ?>">
            <?= tokenHere() ?><input type="hidden" name="do" value="save_settings">
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" name="banner_enabled" id="banner_enabled" value="1" <?= $bannerEnabled ? 'checked' : '' ?>>
              <label class="form-check-label" for="banner_enabled">Show the "you are cloaked" banner</label>
            </div>
            <div class="mb-3">
              <label class="form-label" for="list_order">Picker lists users by</label>
              <select class="form-select" name="list_order" id="list_order">
                <option value="active" <?= $listOrder === 'active' ? 'selected' : '' ?>>Most recently active (last login)</option>
                <option value="registered" <?= $listOrder === 'registered' ? 'selected' : '' ?>>Newest registered (join date)</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label" for="list_limit">Users to show before searching</label>
              <input type="number" min="1" max="200" class="form-control" style="max-width:140px" name="list_limit" id="list_limit" value="<?= safeReturn((string) $listLimit) ?>">
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" name="hide_inactive" id="hide_inactive" value="1" <?= $hideInactive ? 'checked' : '' ?>>
              <label class="form-check-label" for="hide_inactive">Hide deactivated users from the picker</label>
            </div>
            <button type="submit" class="btn cp-btn">Save</button>
          </form>
          <hr>
          <p class="small text-muted mb-0">The core User Manager also has a "Cloak Into User" button. This page is a faster picker, and Cloak+ adds the banner, one-click uncloak, and the logout-to-uncloak behaviour.</p>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-8 mb-4">
      <div class="card">
        <div class="card-header"><i class="fa fa-users me-1"></i> Cloak into a user</div>
        <div class="card-body">
          <form method="get" action="<?= safeReturn(Server::get('PHP_SELF')) ?>" class="row g-2 mb-3">
            <input type="hidden" name="view" value="plugins_config">
            <input type="hidden" name="plugin" value="cloakplus">
            <div class="col-9"><input type="text" name="q" class="form-control" placeholder="Search username, email, or name" value="<?= safeReturn($q) ?>"></div>
            <div class="col-3"><button class="btn btn-outline-secondary w-100">Search</button></div>
          </form>
          <p class="small text-muted mb-2"><?= count($people) ?> user(s) shown<?= $q === '' ? ' (' . safeReturn($orderLabel) . ', limit ' . (int) $listLimit . '). Search to find anyone else.' : ' matching your search (max 50).' ?></p>
          <table class="table table-sm align-middle mb-0">
            <thead><tr><th>ID</th><th>User</th><th>Email</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($people as $p) {
                  $isSelf = ((int) $p->id === (int) $user->data()->id);
                  $isMaster = in_array($p->id, $master_account);
              ?>
                <tr>
                  <td><?= (int) $p->id ?></td>
                  <td><?= safeReturn($p->username) ?><?php if ($p->fname || $p->lname) { ?><br><small class="text-muted"><?= safeReturn(trim($p->fname . ' ' . $p->lname)) ?></small><?php } ?></td>
                  <td><small><?= safeReturn($p->email) ?></small></td>
                  <td class="text-end">
                    <?php if ($isSelf) { ?>
                      <span class="badge bg-secondary">you</span>
                    <?php } elseif ($isMaster) { ?>
                      <span class="badge bg-light text-dark" title="Cloaking into a master account is blocked by default">master</span>
                    <?php } else { ?>
                      <form method="post" action="<?= safeReturn($selfUrl) ?>" class="m-0" data-us-confirm="Cloak into <?= safeReturn($p->username) ?>?\nYou will browse as them until you click Return on the banner.">
                        <?= tokenHere() ?><input type="hidden" name="do" value="cloak"><input type="hidden" name="user_id" value="<?= (int) $p->id ?>">
                        <button class="btn btn-sm cp-btn"><i class="fa fa-user-secret me-1"></i>Cloak</button>
                      </form>
                    <?php } ?>
                  </td>
                </tr>
              <?php } ?>
              <?php if (empty($people)) { ?><tr><td colspan="4" class="text-muted">No users found.</td></tr><?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
