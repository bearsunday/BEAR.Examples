<?php
/**
 * @var \MyVendor\Cms\Entity\Author $author
 */
?>
{{ setLayout('layout/Default') }}
{{ setBlock('bodyClass') ~}}public public-detail public-author{{ endBlock() }}
{{ setBlock('title') ~}}{{h $author->name }} - BEAR.Cms Reference CMS{{ endBlock() }}
{{ setBlock('header') ~}}<h1 class="Author">Author Detail</h1>{{ endBlock() }}
<main>
  <section class="Author">
    <input type="hidden" class="id" value="{{h $author->id }}">
    <h2 class="name">{{h $author->name }}</h2>
    <a class="email" href="mailto:{{h $author->email }}">{{h $author->email }}</a>
    <p class="bio">{{h $author->bio }}</p>
  </section>
</main>
<nav>
  <h2>Links</h2>
  <ul>
    <li><a href="/articlelist?authorId={{h $author->id }}" class="goArticleList" title="Articles by this author.">Articles by this author</a></li>
    <li><a href="/articlelist" class="goArticleList" title="Browse all articles.">Browse all articles</a></li>
    <li><a href="/authorlist" class="goAuthorList">Browse authors</a></li>
    <li><a href="/" class="goIndex">Home</a></li>
  </ul>
</nav>
