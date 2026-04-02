<?php

declare(strict_types=1);

namespace Colibri\View;

use Colibri\Config;

class Page
{
    public string $title = '' {
        get => $this->title !== '' ? $this->title : Config::get('app.name', 'Colibri');
    }

    public string $description = '';

    public string $layout = 'default';

    /** @var array<string, string> */
    public array $meta = [];

    /** @var array<string, string> */
    private array $ogData = [];

    /** @var array<string, string> */
    private array $twitterData = [];

    private ?string $canonicalUrl = null;

    /**
     * Set an OpenGraph property.
     */
    public function og(string $property, string $content): self
    {
        $this->ogData[$property] = $content;

        return $this;
    }

    /**
     * Set a Twitter Card property.
     */
    public function twitter(string $name, string $content): self
    {
        $this->twitterData[$name] = $content;

        return $this;
    }

    /**
     * Set the canonical URL.
     */
    public function canonical(string $url): self
    {
        $this->canonicalUrl = $url;

        return $this;
    }

    /**
     * Render all dynamic meta tags as HTML.
     */
    public function renderMeta(): string
    {
        $html = '';

        // Description
        if ($this->description !== '') {
            $html .= '<meta name="description" content="' . htmlspecialchars($this->description) . '">' . "\n";
        }

        // Custom meta
        foreach ($this->meta as $name => $content) {
            $html .= '<meta name="' . htmlspecialchars($name) . '" content="' . htmlspecialchars($content) . '">' . "\n";
        }

        // OpenGraph
        $ogDefaults = $this->ogDefaults();
        foreach ($ogDefaults as $property => $content) {
            $html .= '<meta property="og:' . htmlspecialchars($property) . '" content="' . htmlspecialchars($content) . '">' . "\n";
        }

        // Twitter Cards
        $twDefaults = $this->twitterDefaults();
        foreach ($twDefaults as $name => $content) {
            $html .= '<meta name="twitter:' . htmlspecialchars($name) . '" content="' . htmlspecialchars($content) . '">' . "\n";
        }

        // Canonical
        if ($this->canonicalUrl !== null) {
            $html .= '<link rel="canonical" href="' . htmlspecialchars($this->canonicalUrl) . '">' . "\n";
        }

        return $html;
    }

    /**
     * Merge OG data with sensible defaults from page properties.
     *
     * @return array<string, string>
     */
    private function ogDefaults(): array
    {
        $defaults = [];

        if ($this->title !== '') {
            $defaults['title'] = $this->title;
        }

        if ($this->description !== '') {
            $defaults['description'] = $this->description;
        }

        // Explicit og() calls override defaults
        return array_merge($defaults, $this->ogData);
    }

    /**
     * Merge Twitter data with sensible defaults.
     *
     * @return array<string, string>
     */
    private function twitterDefaults(): array
    {
        if ($this->twitterData === [] && $this->ogData === []) {
            return [];
        }

        $defaults = ['card' => 'summary'];

        if ($this->title !== '') {
            $defaults['title'] = $this->title;
        }

        if ($this->description !== '') {
            $defaults['description'] = $this->description;
        }

        return array_merge($defaults, $this->twitterData);
    }
}
