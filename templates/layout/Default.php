<?php
/**
 * @var int $cssLevel
 * @var array<int, string> $cssLinks
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ setBlock('title') ~}}BEAR.Cms Reference CMS{{= getBlock() ~}}</title>
  <link rel="profile" href="/profile/alps.json">
  <link rel="stylesheet" href="/css/level{{h $cssLevel }}.css">
</head>
<body>
  <header>
    {{ setBlock('header') ~}}{{= getBlock() ~}}
  </header>
  {{= getContent() }}
  <footer>
    <p>BEAR.Cms Reference CMS</p>
    {{= render('partial/CssSwitcher') }}
  </footer>
</body>
</html>
