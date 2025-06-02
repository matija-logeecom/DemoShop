<?php

namespace DemoShop\Business\Model;

class Category
{
    private ?int $id;
    private string $title;
    private string $code;
    private ?string $description;
    private mixed $parent;

    /**
     * @param string $title
     * @param string $code
     * @param mixed $parent
     * @param string $description
     * @param int|null $id
     */
    public function __construct(
        string $title,
        string $code,
        mixed  $parent = null,
        string $description = '',
        ?int   $id = null
    )
    {
        $this->id = $id;
        $this->title = $title;
        $this->code = $code;
        $this->description = $description;
        $this->parent = $parent;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getParent(): mixed
    {
        return $this->parent;
    }

    /**
     * @return array
     */
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