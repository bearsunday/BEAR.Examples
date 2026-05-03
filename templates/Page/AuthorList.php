<?php
/**
 * @var list<\MyVendor\Cms\Entity\Author> $authors
 */
?>
{{ setLayout('layout/Default') }}
{{ setBlock('bodyClass') ~}}public public-list public-authorlist{{ endBlock() }}
{{ setBlock('title') ~}}Author List - MyVendor.Cms{{ endBlock() }}
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
