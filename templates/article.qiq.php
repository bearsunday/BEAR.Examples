<?php
/**
 * @var \MyVendor\Cms\Entity\Article $article
 * @var \MyVendor\Cms\Entity\Author|null $author
 * @var \MyVendor\Cms\Entity\Category|null $category
 * @var list<array{id:int, slug:string, name:string}> $tags
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{h $article->title }} - BEAR.Cms Reference CMS</title>
  <link rel="profile" href="/profile/alps.json">
  <link rel="stylesheet" href="/css/level1.css">
</head>
<body>
  <header>
    <h1 class="Article">Article Detail</h1>
  </header>
  <main>
    <article class="Article">
      <input type="hidden" class="id" value="{{h $article->id }}">
      <span class="slug">{{h $article->slug }}</span>
      <h2 class="title">{{h $article->title }}</h2>
      <span class="status">{{h $article->status }}</span>
      <?php $publishedAtLabel = $article->publishedAtLabel(); ?>
      <?php if ($publishedAtLabel !== null): ?>
        <time class="publishedAt" datetime="{{h $publishedAtLabel }}">{{h $publishedAtLabel }}</time>
      <?php endif ?>
      <?php $summary = $article->summary(); ?>
      <?php if ($summary !== null): ?>
        <p class="excerpt">{{h $summary }}</p>
      <?php endif ?>
      <div class="body">{{= $article->bodyHtml() }}</div>

      <?php if ($author !== null): ?>
        <section class="Author">
          <input type="hidden" class="id" value="{{h $author->id }}">
          <span class="name">{{h $author->name }}</span>
          <a class="email" href="mailto:{{h $author->email }}">{{h $author->email }}</a>
        </section>
      <?php endif ?>

      <?php if ($category !== null): ?>
        <section class="Category">
          <input type="hidden" class="id" value="{{h $category->id }}">
          <span class="slug">{{h $category->slug }}</span>
          <span class="name">{{h $category->name }}</span>
        </section>
      <?php endif ?>

      <?php foreach ($tags as $tag): ?>
        <section class="Tag">
          <input type="hidden" class="id" value="{{h $tag['id'] }}">
          <span class="slug">{{h $tag['slug'] }}</span>
          <span class="name">{{h $tag['name'] }}</span>
        </section>
      <?php endforeach ?>
    </article>
  </main>
  <nav>
    <h2>Links</h2>
    <ul>
      <li><a href="/articlelist" class="goArticleList" title="Navigate to the paginated article list.">Go to Article List</a></li>
      <?php if ($author !== null): ?>
        <li><a href="/author?id={{h $author->id }}" class="goAuthor" title="Navigate to the author detail.">Go to Author</a></li>
      <?php endif ?>
      <?php if ($category !== null): ?>
        <li><a href="/category?id={{h $category->id }}" class="goCategory" title="Navigate to the category detail.">Go to Category</a></li>
      <?php endif ?>
    </ul>
  </nav>
  <footer>
    <p>BEAR.Cms Reference CMS</p>
  </footer>
</body>
</html>
