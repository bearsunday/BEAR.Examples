<?php
/**
 * @var list<\MyVendor\Cms\Entity\Author> $authors
 */
?>
{{ setLayout('layout/Default') }}
{{ setBlock('title') ~}}Author List - BEAR.Cms Reference CMS{{ endBlock() }}
{{ setBlock('header') ~}}<h1 class="AuthorList">Authors</h1>{{ endBlock() }}
<main>
  <section class="AuthorList">
    <?php if ($authors === []): ?>
      <p>No authors yet.</p>
    <?php endif ?>
    <?php foreach ($authors as $author): ?>
      <article class="Author">
        <input type="hidden" class="id" value="{{h $author->id }}">
        <h2 class="name">
          <a class="goAuthor" href="/author?id={{h $author->id }}">{{h $author->name }}</a>
        </h2>
        <span class="email">{{h $author->email }}</span>
        <?php if ($author->bio !== ''): ?>
          <p class="bio">{{h $author->bio }}</p>
        <?php endif ?>
      </article>
    <?php endforeach ?>
  </section>
</main>
<nav>
  <h2>Links</h2>
  <ul>
    <li><a href="/articlelist" class="goArticleList">Browse all articles</a></li>
    <li><a href="/categorylist" class="goCategoryList">Categories</a></li>
    <li><a href="/taglist" class="goTagList">Tags</a></li>
    <li><a href="/" class="goIndex">Home</a></li>
  </ul>
</nav>
