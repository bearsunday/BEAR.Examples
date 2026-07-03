<?php
/**
 * The author collection is embedded as `authorList` (a lazy Request to
 * `app://self/authors`). The App resource renders itself with
 * templates/App/Authors.php; this page only wraps it in the page layout.
 *
 * @var \BEAR\Resource\Request $authorList
 */
?>
{{ setLayout('layout/Default') }}
{{ setBlock('bodyClass') ~}}public public-list public-authorlist{{ endBlock() }}
{{ setBlock('title') ~}}Author List - BEAR.Kata{{ endBlock() }}
{{ setBlock('header') ~}}<h1 class="AuthorList">Authors</h1>{{ endBlock() }}
<main>
  <section class="AuthorList">
    <?= $authorList ?>
  </section>
</main>
