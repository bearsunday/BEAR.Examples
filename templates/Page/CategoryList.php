<?php
/**
 * @var list<\BEAR\Kata\Entity\Category> $categories
 */
?>
{{ setLayout('layout/Default') }}
{{ setBlock('bodyClass') ~}}public public-list public-categorylist{{ endBlock() }}
{{ setBlock('title') ~}}Category List - BEAR.Kata{{ endBlock() }}
{{ setBlock('header') ~}}<h1 class="CategoryList">Categories</h1>{{ endBlock() }}
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
