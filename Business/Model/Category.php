<?php

namespace DemoShop\Business\Model;

class Category
{
    private ?int $id;
    private string $title;
    private string $code;
    private ?string $description;
    private mixed $parent; // Could be null (for root), int (ID), or string (code/title of parent)

    /**
     * @param string $title
     * @param string $code
     * @param mixed $parent Null for root category, or an identifier (ID, code) of the parent.
     * @param string $description
     * @param int|null $id Optional: ID of the category, typically used for updates.
     */
    public function __construct(
        string $title,
        string $code,
        mixed $parent = null, // Default to null for root categories or if not specified
        string $description = '',
        ?int $id = null
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->code = $code;
        $this->description = $description;
        $this->parent = $parent;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Gets the parent identifier. This could be an ID, a code, or null.
     * The service layer will be responsible for resolving this to an actual parent entity or ID.
     */
    public function getParent(): mixed
    {
        return $this->parent;
    }

    // Optional: A method to convert DTO to an array, useful for repository layer if it expects an array.
    // This keeps the decision of DTO vs array for repository within the service layer.
    public function toArray(): array
    {
        $data = [
            'title' => $this->title,
            'code' => $this->code,
            'description' => $this->description,
            'parent' => $this->parent,
        ];
        if ($this->id !== null) {
            $data['id'] = $this->id;
        }
        return $data;
    }
}