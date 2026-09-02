<?php

if (! function_exists('ui_icon')) {
    function ui_icon(string $name, string $class = ''): string
    {
        $safe = preg_replace('/[^a-z0-9-]/i', '', $name) ?: 'circle';
        $classAttr = trim('ui-icon ' . $class);
        return '<svg class="' . esc($classAttr, 'attr') . '" aria-hidden="true"><use href="' . base_url('assets/icons/ui.svg') . '#' . esc($safe, 'attr') . '"></use></svg>';
    }
}

if (! function_exists('role_label')) {
    function role_label(string $role): string
    {
        return config('BantayGamit')->roles[$role] ?? ucwords(str_replace('_', ' ', $role));
    }
}

if (! function_exists('status_label')) {
    function status_label(string $status): string
    {
        return ucwords(str_replace('_', ' ', $status));
    }
}

if (! function_exists('status_class')) {
    function status_class(string $status): string
    {
        return match ($status) {
            'active','available','approved','returned','completed','excellent','good' => 'success',
            'pending','scheduled','fair' => 'warning',
            'rejected','cancelled','damaged','overdue','inactive','retired' => 'danger',
            'released','in_progress','maintenance' => 'info',
            default => 'neutral',
        };
    }
}

if (! function_exists('fmt_date')) {
    function fmt_date(?string $value, bool $withTime = false): string
    {
        if (! $value) return '—';
        $time = strtotime($value);
        return $time ? date($withTime ? 'M j, Y g:i A' : 'M j, Y', $time) : '—';
    }
}
