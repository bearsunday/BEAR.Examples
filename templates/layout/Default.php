<?php
/**
 * @var int $cssLevel
 * @var array<int, string> $cssLinks
 * @var \MyVendor\Cms\Auth\UserInterface $user
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ setBlock('title') ~}}MyVendor.Cms{{= getBlock() ~}}</title>
  <link rel="profile" href="/profile/alps.json">
  <link rel="stylesheet" href="/css/level{{h $cssLevel }}.css">
</head>
<body class="{{ setBlock('bodyClass') ~}}public public-list{{= getBlock() ~}}">
  <nav class="SiteNav">
    <a class="goIndex brand" href="/">MyVendor.Cms</a>
    <ul class="browse">
      <li><a class="goArticleList" href="/articlelist">Articles</a></li>
      <li><a class="goAuthorList" href="/authorlist">Authors</a></li>
      <li><a class="goCategoryList" href="/categorylist">Categories</a></li>
      <li><a class="goTagList" href="/taglist">Tags</a></li>
    </ul>
    <?php if ($user instanceof \MyVendor\Cms\Auth\AdminUserInterface): ?>
      <a class="goAdminIndex" href="/admin/index">Admin</a>
      <a class="doLogout" href="/admin/logout">Sign out</a>
    <?php else: ?>
      <a class="goSignIn" href="/admin/login">Sign in</a>
    <?php endif ?>
  </nav>
  <header>
    {{ setBlock('header') ~}}{{= getBlock() ~}}
  </header>
  {{= getContent() }}
  <footer>
    <p>MyVendor.Cms</p>
    {{= render('partial/CssSwitcher') }}
  </footer>
</body>
</html>
