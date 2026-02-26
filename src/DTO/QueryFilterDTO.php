<?php

namespace Keyboardman\FilemanagerBundle\DTO;

class QueryFilterDTO
{
    public string $filesystem;

    public string $path;

    public ?string $type = '';

    public ?string $sort = 'name_asc';

    public ?string $mode = null;

    public ?string $target = null;

    public ?bool $crossdomain = false;

    public static function create(string $filesystem, string $path, ?string $mode = null, ?string $target = null, ?string $type = null, ?string $sort = 'name_asc', ?bool $crossdomain = false): QueryFilterDTO
    {
        $self = new self();
        $self->filesystem = $filesystem;
        $self->path = $path;
        $self->mode = $mode;
        $self->target = $target;
        $self->type = $type;
        $self->sort = $sort;
        $self->crossdomain = $crossdomain;

        return $self;
    }
}
