<?php
/**
 * @var \MyVendor\Cms\Entity\Tag $tag
 * @var list<\MyVendor\Cms\Entity\Article> $articles
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{h $tag->name }} - BEAR.Cms Reference CMS</title>
  <link rel="profile" href="/profile/alps.json">
  <link rel="stylesheet" href="/css/level{{h $cssLevel }}.css">
</head>
<body>
  <header>
    <h1 class="Tag">Tag Detail</h1>
  </header>
  <main>
    <section class="Tag">
      <input type="hidden" class="id" value="{{h $tag->id }}">
      <h2 class="name">{{h $tag->name }}</h2>
      <span class="slug">{{h $tag->slug }}</span>
    </section>

    <section class="ArticleList">
      <h2>Tagged articles</h2>
      <?php if ($articles === []): ?>
        <p>No articles tagged with this tag yet.</p>
      <?php endif ?>
      <?php foreach ($articles as $article): ?>
        <article class="Article">
          <input type="hidden" class="id" value="{{h $article->id }}">
          <h3 class="title">
            <a class="goArticle" href="/article?id={{h $article->id }}">{{h $article->title }}</a>
          </h3>
          <span class="slug">{{h $article->slug }}</span>
          <?php $publishedAtLabel = $article->publishedAtLabel(); ?>
          <?php if ($publishedAtLabel !== null): ?>
            <time class="publishedAt" datetime="{{h $publishedAtLabel }}">{{h $publishedAtLabel }}</time>
          <?php endif ?>
        </article>
      <?php endforeach ?>
    </section>
  </main>
  <nav>
    <h2>Links</h2>
    <ul>
      <li><a href="/articlelist?tagId={{h $tag->id }}" class="goArticleList">All articles with this tag</a></li>
      <li><a href="/taglist" class="goTagList">Browse tags</a></li>
      <li><a href="/" class="goIndex">Home</a></li>
    </ul>
  </nav>
  <footer>
    <p>BEAR.Cms Reference CMS</p>
    <p class="cssSwitcher">CSS:
      <?php foreach ([1, 2, 3] as $n): ?>
        <?php if ($n === $cssLevel): ?>
          <strong>level{{h $n }}</strong>
        <?php else: ?>
          <a href="{{h $cssLinks[$n] }}">level{{h $n }}</a>
        <?php endif ?>
      <?php endforeach ?>
    </p>
  </footer>
</body>
</html>
