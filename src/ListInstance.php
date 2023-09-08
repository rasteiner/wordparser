<?php 

namespace rasteiner\Wordparser;

use WeakMap;

class ListInstance {

    public int $defaultLevel = 0;

    public function __construct(
        public readonly string $id,
        public readonly string $abstractId,
        public readonly ?array $levelOverrides,
        public readonly Numbering $numbering
    ) {
        
    }

    public function current(?int $level = null) {
        if($level === null) $level = $this->defaultLevel;

        // does this have an override for level? 
        if($override = $this->levelOverrides[$level]['startOverride'] ?? null) {
            $this->numbering->reset($this->abstractId, $level, $override);
        }

        return $this->numbering->current($this->abstractId, $level);
    }

    public function isExplicit(): bool {
        return is_numeric($this->id);
    }

    public function getAbstract(): ?array {
        return $this->numbering->getAbstract($this->abstractId);
    }

    public function htmlType(?int $level): array {
        if($level === null) $level = $this->defaultLevel;

        $level = $this->getAbstract()['levels'][$level];
        if($level === null) return ['ul'];


        return match($level['numFmt'] ?? null) {
            'decimal' => ['ol', 'decimal'],
            'lowerLetter' => ['ol', 'lower-alpha'],
            'upperLetter' => ['ol', 'upper-alpha'],
            'lowerRoman' => ['ol', 'lower-roman'],
            'upperRoman' => ['ol', 'upper-roman'],
            'none' => ['ul', 'none'],
            'bullet' => ['ul', match($level['lvlText']) {
                'o' => 'circle',
                '' => 'disc',
                '' => 'square',
                default => null
            }],
            default => ['ul', null]
        };
    }

    public function next(?int $level = null) {
        if($level === null) $level = $this->defaultLevel;

        return $this->numbering->next($this->abstractId, $level);
    }

    public function render(?int $level = null) {
        if($level === null) $level = $this->defaultLevel;

        // does this have an override for level? 
        if($override = $this->levelOverrides[$level]['startOverride'] ?? null) {
            $this->numbering->reset($this->abstractId, $level, $override);
        }

        return $this->numbering->render($this->abstractId, $level);
    }
}