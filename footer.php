<?php
// Persistent "you are cloaked" banner. Shows on every page while a cloak is active, unless the
// admin disabled it on the plugin config page. One click returns to the real account (no logout).
if (function_exists('isCloaked') && isCloaked() && cloakPlusBannerEnabled()) {
    global $db, $user, $us_url_root;
    $cloakToName = $user->isLoggedIn() ? $user->data()->username : ('user #' . cloakTo());
    $fromId = function_exists('cloakFrom') ? cloakFrom() : null;
    $fromName = 'your account';
    if ($fromId) {
        $fr = $db->query('SELECT username FROM users WHERE id = ?', [(int) $fromId]);
        if ($fr->count()) {
            $fromName = $fr->first()->username;
        }
    }
    ?>
    <div id="cloakplus-banner" style="position:fixed;left:0;right:0;bottom:0;z-index:1080;background:linear-gradient(135deg,#2ea5cb 0%,#2edcb7 100%);color:#fff;padding:.5rem 1rem;display:flex;align-items:center;justify-content:center;gap:1rem;box-shadow:0 -2px 8px rgba(0,0,0,.25);font-size:.95rem;">
      <span><i class="fa fa-user-secret me-1"></i> You are cloaked as <strong><?= safeReturn($cloakToName) ?></strong> &middot; real account: <strong><?= safeReturn($fromName) ?></strong></span>
      <form method="post" action="<?= $us_url_root ?>usersc/plugins/cloakplus/files/uncloak.php" style="margin:0;">
        <input type="hidden" name="csrf" value="<?= Token::generate() ?>">
        <button type="submit" class="btn btn-sm btn-light" style="font-weight:600;"><i class="fa fa-arrow-rotate-left me-1"></i>Return to your account</button>
      </form>
    </div>
    <style>body{padding-bottom:3.25rem;}</style>
<?php } ?>
