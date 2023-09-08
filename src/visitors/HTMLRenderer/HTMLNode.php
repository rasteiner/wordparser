<?php 

namespace rasteiner\Wordparser\visitors\HTMLRenderer;

use Stringable;

/**
 * Represents either an HTML tag or an HTMLFragment (when $tag is null)
 */
class HTMLNode implements Stringable {
    protected $style = [];
    protected static $selfClosing = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'
    ];

    public function __construct(
        public ?string $tag = null,
        public array $attributes = [],
        public array $children = [],
        public ?HTMLNode $parent = null,
    )
    {}

    public function __toString(): string {
        $children = join('', $this->children);

        if($this->tag) {
            if(count($this->style)) {
                $this->attributes['style'] = join('; ', array_map(fn($key, $value) => "$key: $value", array_keys($this->style), $this->style));
            }

            $attributes = array_filter($this->attributes);
            $attributes = array_map(fn($key, $value) => "$key=\"$value\"", array_keys($this->attributes), $this->attributes);
            $attributes = join(' ', $attributes);

            if($attributes !== '') $attributes = ' ' . $attributes;
            if(in_array($this->tag, self::$selfClosing)) {
                return "<{$this->tag}$attributes />";
            } else {
                return "<{$this->tag}$attributes>$children</{$this->tag}>";
            }
        } else {
            return $children;
        }
    }

    public function append(?HTMLNode ...$node) {
        foreach($node as $n) {
            if($n === null) continue;
            $n->parent = $this;
            $this->children[] = $n;
        }
    }

    public function lastChild(): ?HTMLNode {
        return $this->children[count($this->children) - 1] ?? null;
    }

    public function style(null|string|array $property = null, ?string $value = null) {
        if(is_array($property)) {
            foreach($property as $key => $value) {
                $this->style[$key] = $value;
            }
            return;
        }
        if($property === null) return $this->style;
        if($value === null) return $this->style[$property] ?? null;

        $this->style[$property] = $value;
    }

    public function removeStyle() {
        $this->style = [];
    }
}