<?php
/**
 * @var \BEAR\Examples\Entity\Author|null $author
 */
if (! isset($author) || $author === null) {
    throw new \BEAR\Examples\Exception\AuthorNotFoundException();
}
?>
{{ setLayout('layout/Default') }}
{{ setBlock('bodyClass') ~}}public public-detail public-author{{ endBlock() }}
{{ setBlock('title') ~}}{{h $author->name }} - BEAR.Examples{{ endBlock() }}
{{ setBlock('header') ~}}<h1 class="Author">Author Detail</h1>{{ endBlock() }}
<main>
  <section class="Author">
    <input type="hidden" class="id" value="{{h $author->id }}">
    <h2 class="name">{{h $author->name }}</h2>
    <a class="email" href="mailto:{{h $author->email }}">{{h $author->email }}</a>
    <p class="bio">{{h $author->bio }}</p>
  </section>
</main>
<nav class="Related">
  <ul>
    <li><a href="/articlelist?authorId={{h $author->id }}" class="goArticleList" title="Articles by this author.">Articles by {{h $author->name }}</a></li>
  </ul>
</nav>
