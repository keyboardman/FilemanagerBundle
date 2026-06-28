<?php

namespace Keyboardman\FilemanagerBundle\DTO;

/**
 * Filtres de requête pour le filemanager (disque, chemin, type média, mode iframe).
 */
class QueryFilterDTO
{
    /** Identifiant du disque Flysystem ciblé. */
    public string $filesystem;

    /** Chemin du répertoire courant. */
    public string $path;

    /** Filtre par type média (image, audio, video). */
    public ?string $type = '';

    /** Ordre de tri (name_asc ou name_desc). */
    public ?string $sort = 'name_asc';

    /** Mode d'ouverture (ex. iframe). */
    public ?string $mode = null;

    /** Identifiant HTML du champ cible pour la sélection. */
    public ?string $target = null;

    /** Active le mode cross-domain pour l'iframe. */
    public ?bool $crossdomain = false;

    /** Mode d'affichage des fichiers (card ou list). */
    public string $view = 'card';

    /**
     * Fabrique un DTO à partir des paramètres de requête.
     */
    public static function create(string $filesystem, string $path, ?string $mode = null, ?string $target = null, ?string $type = null, ?string $sort = 'name_asc', ?bool $crossdomain = false, string $view = 'card'): QueryFilterDTO
    {
        $self = new self();
        $self->filesystem = $filesystem;
        $self->path = $path;
        $self->mode = $mode;
        $self->target = $target;
        $self->type = $type;
        $self->sort = $sort;
        $self->crossdomain = $crossdomain;
        $self->view = $view;

        return $self;
    }
}
