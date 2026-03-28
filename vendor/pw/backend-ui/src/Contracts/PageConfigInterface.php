<?php
// src/Contracts/PageConfigInterface.php

namespace PW\BackendUI\Contracts;

/**
 * Optional interface for plugins that want to define page configuration
 * in a structured way instead of passing arrays.
 */
interface PageConfigInterface {

    /**
     * Page title shown in the header.
     */
    public function get_title(): string;

    /**
     * Short description under the title.
     */
    public function get_description(): string;

    /**
     * Tab definitions.
     * @return array [ [ 'slug' => '', 'label' => '', 'active' => false ], ... ]
     */
    public function get_tabs(): array;

    /**
     * Render the main content area.
     */
    public function render_content(): void;
}
