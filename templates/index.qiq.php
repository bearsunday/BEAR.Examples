<?php
/**
 * @var list<\MyVendor\Cms\Entity\Article> $articles
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BEAR.Cms Reference CMS</title>
  <link rel="profile" href="/profile/alps.json">
  <link rel="stylesheet" href="/css/level1.css">
</head>
<body>
  <header>
    <h1 class="ArticleList">BEAR.Cms Reference CMS</h1>
  </header>
  <main>
    <section class="ArticleList">
      <?php if ($articles === []): ?>
        <p>No published articles.</p>
      <?php endif ?>
      <?php foreach ($articles as $article): ?>
        <article class="Article">
          <input type="hidden" class="id" value="{{h $article->id }}">
          <h2 class="title">
            <a class="goArticle" href="/article?id={{h $article->id }}">{{h $article->title }}</a>
          </h2>
          <span class="slug">{{h $article->slug }}</span>
          <span class="status">{{h $article->status }}</span>
          <?php $publishedAtLabel = $article->publishedAtLabel(); ?>
          <?php if ($publishedAtLabel !== null): ?>
            <time class="publishedAt" datetime="{{h $publishedAtLabel }}">{{h $publishedAtLabel }}</time>
          <?php endif ?>
          <?php $summary = $article->summary(); ?>
          <?php if ($summary !== null): ?>
            <p class="excerpt">{{h $summary }}</p>
          <?php endif ?>
        </article>
      <?php endforeach ?>
    </section>
  </main>
  <nav>
    <h2>Demo entry points</h2>
    <ul>
      <li><a href="/article?id=1" class="goArticle">First article (id=1)</a></li>
      <li><a href="/mock/html/article.html">Static design mock (no PHP)</a></li>
    </ul>
  </nav>
  <footer>
    <p>BEAR.Cms Reference CMS</p>
  </footer>
</body>
</html>
