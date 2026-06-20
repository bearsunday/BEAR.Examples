<?php
/**
 * @var \BEAR\Examples\Auth\AdminUserInterface $admin
 */
?>
{{ setLayout('layout/Default') }}
{{ setBlock('bodyClass') ~}}admin admin-index{{ endBlock() }}
{{ setBlock('title') ~}}Admin - BEAR.Examples{{ endBlock() }}
{{ setBlock('header') ~}}<h1 class="AdminIndex">Admin</h1>{{ endBlock() }}
<main>
  <section class="AdminIndex">
    <h2>Manage</h2>
    <ul>
      <li><a href="/admin/articlelist" class="goAdminArticleList">Articles</a></li>
      <li><a href="/admin/article" class="doCreateArticle">New article</a></li>
    </ul>
  </section>
</main>
