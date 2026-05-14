<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Cli\Attribute\Cli;
use BEAR\Cli\Attribute\Option;
use BEAR\RepositoryModule\Annotation\Purge;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Query\CategoryCommandInterface;
use MyVendor\Cms\Query\CategoryQueryInterface;

use function assert;

#[Alps('Category')]
class Category extends ResourceObject
{
    public function __construct(
        private readonly CategoryQueryInterface $category,
        private readonly CategoryCommandInterface $categoryCmd,
    ) {
    }

    #[Alps('goCategory')]
    #[Link(rel: 'goCategoryList', href: 'app://self/categories')]
    #[Link(rel: 'goArticleList', href: 'app://self/articles{?categoryId}')]
    #[JsonSchema('category.json')]
    #[Cli(name: 'category-show', description: 'Show a category by id', output: 'name')]
    public function onGet(
        #[Option(shortName: 'i', description: 'Category id')]
        int $id,
    ): static {
        $category = $this->category->item($id);
        if ($category === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Category not found', 'id' => $id];

            return $this;
        }

        $this->body = [
            'id' => $category->id,
            'slug' => $category->slug,
            'name' => $category->name,
            'description' => $category->description,
            'parentId' => $category->parentId,
        ];

        return $this;
    }

    #[Alps('doCreateCategory')]
    #[JsonSchema(schema: 'write_response.json', params: 'category_create.json')]
    #[Purge(uri: 'app://self/categories')]
    #[Cli(name: 'category-add', description: 'Create a new category')]
    public function onPost(
        #[Option(shortName: 's', description: 'Category slug')]
        string $slug,
        #[Option(shortName: 'n', description: 'Category name')]
        string $name,
        #[Option(shortName: 'd', description: 'Category description')]
        string|null $description = null,
        #[Option(shortName: 'p', description: 'Parent category id')]
        int|null $parentId = null,
    ): static {
        $this->categoryCmd->add($slug, $name, $description, $parentId);
        // bySlug after add is invariant per docs/conventions.md §4.
        $created = $this->category->bySlug($slug);
        assert($created !== null);
        $this->code = Code::CREATED;
        $this->headers['Location'] = '/category?id=' . $created->id;
        $this->body = ['id' => $created->id, 'slug' => $slug];

        return $this;
    }

    #[Alps('doUpdateCategory')]
    #[JsonSchema(schema: 'write_response.json', params: 'category_update.json')]
    #[Purge(uri: 'app://self/categories')]
    #[Cli(name: 'category-update', description: 'Update an existing category')]
    public function onPut(
        #[Option(shortName: 'i', description: 'Category id')]
        int $id,
        #[Option(shortName: 'n', description: 'Category name')]
        string $name,
        #[Option(shortName: 'd', description: 'Category description')]
        string|null $description = null,
        #[Option(shortName: 'p', description: 'Parent category id')]
        int|null $parentId = null,
    ): static {
        if ($this->category->item($id) === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Category not found', 'id' => $id];

            return $this;
        }

        $this->categoryCmd->update($id, $name, $description, $parentId);
        $this->code = Code::OK;
        $this->body = ['id' => $id];

        return $this;
    }

    #[Alps('doDeleteCategory')]
    #[Purge(uri: 'app://self/categories')]
    #[Cli(name: 'category-delete', description: 'Delete a category')]
    public function onDelete(
        #[Option(shortName: 'i', description: 'Category id')]
        int $id,
    ): static {
        if ($this->category->item($id) === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Category not found', 'id' => $id];

            return $this;
        }

        $this->categoryCmd->delete($id);
        $this->code = Code::NO_CONTENT;
        $this->body = [];

        return $this;
    }
}
