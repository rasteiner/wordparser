<?php 

namespace rasteiner\Wordparser;

use SimpleXMLElement;

class Style {

    
    public function __construct(
        public readonly ?string $id = null,
        protected ?Style $basedOn = null,
        protected array $properties = []
    )
    {
        
    }

    public function basedOn(): ?Style {
        return $this->basedOn;
    }

    public function withBasedOn(?Style $basedOn): self {
        $this->basedOn = $basedOn;
        return $this;
    }

    public function clone(): self {
        return clone $this;
    }

    public function __get($name): bool|int|string|null
    {
        return $this->properties[$name] ?? (
            $this->basedOn ? $this->basedOn->$name : null
        );
    }

    public function isEmpty() {
        return count($this->properties) === 0;
    }

    public function numbering(Numbering $numbering): ?ListInstance {
        if($this->id and $list = $numbering->get($this->id)) {
            return $list;
        } else if($this->basedOn) {
            return $this->basedOn->numbering($numbering);
        } else {
            return null;
        }
    }

    public function equals(Style|array $other): bool {
        // check if all my properties are in the other style and vice versa
        $otherProps = $other instanceof Style ? $other->properties : $other;
        $otherProps = array_filter($otherProps, fn($v) => $v !== null);
        $thisProps = array_filter($this->properties, fn($v) => $v !== null);

        if(count($otherProps) !== count($thisProps)) return false;
        if(count(array_diff_key($otherProps, $thisProps)) > 0) return false;

        foreach($thisProps as $key => $value) {
            if($otherProps[$key] !== $value) return false;
        }

        return true;
    }

    public function includes(Style|array $other): bool {
        // check if all other properties are in my style
        $otherProps = $other instanceof Style ? $other->properties : $other;
        $otherProps = array_filter($otherProps, fn($v) => $v !== null);

        foreach($otherProps as $key => $value) {
            if(!isset($this->properties[$key])) return false;
            if($this->properties[$key] !== $value) return false;
        }

        return true;
    }

    public function unset(string ...$properties): self {
        foreach($properties as $prop) {
            unset($this->properties[$prop]);
        }

        return $this;
    }

    public function merge(Style|array $other): self {
        $otherProps = $other instanceof Style ? $other->properties : $other;
        $otherProps = array_filter($otherProps, fn($v) => $v !== null);

        $this->properties = array_merge($this->properties, $otherProps);

        return $this;
    }

    public function flat(): self {
        $props = $this->properties;
        if($this->basedOn) {
            $props = array_merge($this->basedOn->flat()->properties, $props);
        }

        return new self(properties: $props);
    }

    public function toCSSProperties() {
        $props = $this->flat()->properties;

        $css = [];

        if($props['bold'] ?? false) $css['font-weight'] = 'bold';
        if($props['italic'] ?? false) $css['font-style'] = 'italic';
        if($props['underline'] ?? false) $css['text-decoration'] = 'underline';
        if($props['strike'] ?? false) $css['text-decoration'] = 'line-through';
        if($props['smallCaps'] ?? false) $css['font-variant'] = 'small-caps';

        if($props['color'] ?? false) $css['color'] = '#' . $props['color'];
        if($props['background'] ?? false) $css['background-color'] = '#' . $props['background'];

        if($props['font'] ?? false) $css['font-family'] = $props['font'];
        if($props['size'] ?? false) $css['font-size'] = ($props['size']/2) . 'pt';

        return $css;        
    }

    public function toCSS() {
        $props = $this->toCSSProperties();

        return join(';', array_map(fn($k, $v) => "$k: $v", array_keys($props), $props));
    }

    static function parseXML(SimpleXMLElement $props): array {
        $properties = [];

        // bold
        $properties['bold'] = count($props->xpath('w:b')) > 0 ? true : null;

        // italic
        $properties['italic'] = count($props->xpath('w:i')) > 0 ? true : null;

        // underline
        $properties['underline'] = count($props->xpath('w:u')) > 0 ? true : null;

        // strike
        $properties['strike'] = count($props->xpath('w:strike')) > 0 ? true : null;

        // small caps
        $properties['smallCaps'] = count($props->xpath('w:smallCaps')) > 0 ? true : null;

        // color
        $color = $props->xpath('w:color/@w:val')[0] ?? null;
        if ($color) {
            $properties['color'] = (string)$color;
        }

        // font
        $font = $props->xpath('w:rFonts/@w:ascii')[0] ?? null;
        if ($font) {
            $properties['font'] = (string)$font;
        }

        // size
        $size = $props->xpath('w:sz/@w:val')[0] ?? null;
        if ($size) {
            $properties['size'] = (int) $size;
        }

        // shading
        $shading = $props->xpath('w:shd/@w:fill')[0] ?? null;
        if ($shading) {
            $properties['background'] = (string) $shading;
        }

        return $properties;
    }
}