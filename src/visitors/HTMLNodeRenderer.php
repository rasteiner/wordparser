<?php 

namespace rasteiner\Wordparser\visitors;

use rasteiner\Wordparser\Node;
use rasteiner\Wordparser\nodes\Body;
use rasteiner\Wordparser\nodes\FootnoteReference;
use rasteiner\Wordparser\nodes\Heading;
use rasteiner\Wordparser\nodes\Image;
use rasteiner\Wordparser\nodes\Link;
use rasteiner\Wordparser\nodes\Paragraph;
use rasteiner\Wordparser\nodes\Run;
use rasteiner\Wordparser\nodes\Table;
use rasteiner\Wordparser\nodes\TableCell;
use rasteiner\Wordparser\nodes\TableRow;
use rasteiner\Wordparser\nodes\Text;
use rasteiner\Wordparser\visitors\HTMLRenderer\HTMLNode;
use Traversable;

/**
 * @extends Visitor<HTMLNode>
 */
class HTMLNodeRenderer extends Visitor {
    protected $tagsToStyle = [
        'em' => ['italic' => true],
        'strong' => ['bold' => true],
        'u' => ['underline' => true],
        'strike' => ['strike' => true],
        'sub' => ['subscript' => true],
        'sup' => ['superscript' => true],
        'x-sc' => ['smallCaps' => true],
    ];

    public function __construct(protected bool $noBlocks = false)
    {}

    protected function combine(iterable $a, iterable $b): Traversable {
        yield from $a;
        yield from $b;
    }

    public function mergeByStyle(Node ...$nodes): Traversable {
        // merge adjacent runs with same style
        for($i = 0; $i < count($nodes); $i++) {
    
            $a = $nodes[$i];

            while($a instanceof Run && $i < count($nodes) - 1 and $b = $nodes[$i + 1] and $b instanceof Run) {
                if($a->style->equals($b->style)) {
                    $a = new Run(
                        $a->style,
                        $this->combine($a->children(), $b->children())
                    );
                    $i++;
                } else {
                    break;
                }
            }
            
            yield $a;
        }
    }

    public function visitHardBreak(): HTMLNode {
        return new HTMLNode('br');
    }

    public function visitImage(Image $image): HTMLNode {
        list($width, $height) = $image->info();

        $attributes = [
            'src' => $image->getDataUri(),
            'alt' => $image->name,
            'width' => $width,
            'height' => $height,
        ];

        return new HTMLNode('img', $attributes);
    }

    public function visitBody(Body $body) {
        $node = new HTMLNode();

        $node->append(...$this->visitMany(...$body->children()));

        return $node;
    }

    public function visitRun(Run $run): HTMLNode {
        $node = new HTMLNode();
        $appendTarget = $node;
        $style = $run->getStyle()->clone()->flat();

        foreach($this->tagsToStyle as $tag => $styleChanges) {
            if($style->includes($styleChanges)) {
                $newChild = new HTMLNode($tag);
                $appendTarget->append($newChild);
                $style->unset(...array_keys($styleChanges));
                $appendTarget = $newChild;
            }
        }
        
        foreach($run->children as $child) {
            $appendTarget->append($this->visit($child));
        }

        $props = $style->toCSSProperties();
        if(count($props)) {
            $node->tag = 'span';
            foreach($props as $key => $value) {
                $node->style($key, $value);
            }
        }

        return $node;
    }

    protected function assembleList(Node ...$nodes): Traversable {
        for($i = 0; $i < count($nodes); $i++) {
            $n = $nodes[$i];
            
            if(!($n instanceof Heading) && $n instanceof Paragraph && $n->list && $n->listLevel === 0 && $n->list->isExplicit()) {
                $list = $n->list;
                $abstractId = $list->abstractId;

                $type = $list->htmlType($n->listLevel);
                
                $start = $list->current(0);
                $list = new HTMLNode($type[0]);
                if($start > 1 && $type[0] === 'ol') {
                    $list->attributes['start'] = $start;
                }
                if($type[1] ?? null) {
                    $list->style('list-style-type', $type[1]);
                }
                
                
                // organize following paragraphs into hierarchy
                $stack = [$list];
                $item = $n;
                do {
                    $node = new HTMLNode('li', children: $this->visitMany(...$item->children()));
            
                    if ($item->listLevel + 1 > count($stack)) {
                        $ol = new HTMLNode('ol');
                        end($stack)->lastChild()->append($ol);
                        $stack[] = $ol;
                    } else if($item->listLevel + 1 < count($stack)) {
                        while ($item->listLevel + 1 < count($stack)) {
                            array_pop($stack);
                        }
                    }

                    $item->list->next($item->listLevel);
                    end($stack)->append($node);

                    $i += 1;
                } while($i < count($nodes) and $item = $nodes[$i] and $item instanceof Paragraph and $item->list?->abstractId === $abstractId);

                yield $list;
                
            } else {
                yield $this->visit($n);
            }
        }
    }
    
    public function visitMany(Node ...$nodes) {
        $merged = $this->mergeByStyle(...$nodes);

        return [...$this->assembleList(...$merged)];
    }

    public function visitHeading(Heading $heading) {
        $children = $this->visitMany(...$heading->children());

        $node = new HTMLNode('h' . $heading->level);
        if($heading->list) {
            $num = $heading->list->render($heading->listLevel);
            $list = new HTMLNode(children: ["$num "]);
            $heading->list->next($heading->listLevel);
        }

        // move style up to parent
        if(count($children) === 1 and $children[0]->tag === 'span') {
            $node->style($children[0]->style());
            $children = $children[0]->children;
        }

        $node->append($list ?? null, ...$children);
        return $node;
    }

    public function visitLink(Link $link) {
        $node = new HTMLNode('a', [
            'href' => $link->url,
        ]);
        $node->append(...$this->visitMany(...$link->children()));
        return $node;
    }

    public function visitParagraph(Paragraph $p) {
        $children = $this->visitMany(...$p->children());
        if(count($children) === 0) return null;
        
        $node = new HTMLNode($this->noBlocks ? 'span' : 'p');
        if($p->list) {
            $num = $p->list->render($p->listLevel);
            $list = new HTMLNode(children: [$num]);
            $p->list->next($p->listLevel);
        }
        $node->append($list ?? null, ...$children);
        return $node;
    }

    public function visitTable(Table $table) {
        $node = new HTMLNode('table');
        $node->append(...$this->visitMany(...$table->children()));
        return $node;
    }

    public function visitTableRow(TableRow $row) {
        $node = new HTMLNode('tr');
        $node->append(...$this->visitMany(...$row->children()));
        return $node;
    }

    public function visitTableCell(TableCell $cell) {
        $node = new HTMLNode('td');
        $style = $cell->getStyle()->toCSSProperties();
        if($style) {
            $node->style($style);
        }
        $node->append(...$this->visitMany(...$cell->children()));
        return $node;
    }

    public function visitFootnoteReference(FootnoteReference $fn) {
        $converter = new self(noBlocks: true);

        $node = new HTMLNode('x-footnote');
        $node->append(...$converter->visitMany(...$fn->children()));
        return $node;
    }

    public function visitText(Text $text) {
        return new HTMLNode(children:[
            $text->text
        ]);
    }
}
