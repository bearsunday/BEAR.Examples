<?php
/**
 * @var list<\MyVendor\Cms\Entity\Category> $categories
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Category List - BEAR.Cms Reference CMS</title>
  <link rel="profile" href="/profile/alps.json">
  <link rel="stylesheet" href="/css/level1.css">
</head>
<body>
  <header>
    <h1 class="CategoryList">Categories</h1>
  </header>
  <main>
    <section class="CategoryList">
      <?php if ($categories === []): ?>
        <p>No categories defined yet.</p>
      <?php endif ?>
      <?php foreach ($categories as $category): ?>
        <article class="Category">
          <input type="hidden" class="id" value="{{h $category->id }}">
          <h2 class="name">
            <a class="goCategory" href="/category?id={{h $category->id }}">{{h $category->name }}</a>
          </h2>
          <span class="slug">{{h $category->slug }}</span>
          <?php if ($category->parentId !== null): ?>
            <span class="parentId">Parent: #{{h $category->parentId }}</span>
          <?php endif ?>
          <?php if ($category->description !== null): ?>
            <p class="description">{{h $category->description }}</p>
          <?php endif ?>
        </article>
      <?php endforeach ?>
    </section>
  </main>
  <nav>
    <h2>Links</h2>
    <ul>
      <li><a href="/articlelist" class="goArticleList">Browse all articles</a></li>
      <li><a href="/" class="goIndex">Home</a></li>
    </ul>
  </nav>
  <footer>
    <p>BEAR.Cms Reference CMS</p>
  </footer>
</body>
</html>
