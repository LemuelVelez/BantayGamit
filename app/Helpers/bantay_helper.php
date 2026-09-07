<?php

if (! function_exists('ui_icon')) {
    function ui_icon(string $name, string $class = ''): string
    {
        $safe = preg_replace('/[^a-z0-9-]/i', '', $name) ?: 'circle';
        $classAttr = trim('ui-icon ' . $class);
        return '<svg class="' . esc($classAttr, 'attr') . '" aria-hidden="true"><use href="' . base_url('assets/icons/ui.svg') . '#' . esc($safe, 'attr') . '"></use></svg>';
    }
}


if (! function_exists('current_role')) {
    function current_role(): string
    {
        return (string) session()->get('role');
    }
}

if (! function_exists('current_user_id')) {
    function current_user_id(): int
    {
        return (int) session()->get('user_id');
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

if (! function_exists('form_error')) {
    function form_error(string $field): string
    {
        $errors = session()->getFlashdata('errors');
        if (! is_array($errors) || empty($errors[$field])) return '';
        $id = 'error-' . (preg_replace('/[^a-z0-9_-]/i', '-', $field) ?: 'field');
        return '<small class="field-error" id="' . esc($id, 'attr') . '">' . esc((string) $errors[$field]) . '</small>';
    }
}

if (! function_exists('form_invalid')) {
    function form_invalid(string $field): string
    {
        $errors = session()->getFlashdata('errors');
        if (! is_array($errors) || empty($errors[$field])) return '';
        $id = 'error-' . (preg_replace('/[^a-z0-9_-]/i', '-', $field) ?: 'field');
        return ' aria-invalid="true" aria-describedby="' . esc($id, 'attr') . '"';
    }
}
