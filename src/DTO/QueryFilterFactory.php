<?php

namespace Keyboardman\FilemanagerBundle\DTO;

use Keyboardman\FilemanagerBundle\Disk\DiskManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class QueryFilterFactory
{
    public function __construct(private readonly DiskManager $diskManager)
    {
    }

    public function create(Request $request): QueryFilterDTO
    {
        $filesystem = $request->query->get('filesystem') ?? $this->diskManager->names()[0];

        $path = $request->query->get('path') ?? '';

        $mode = $request->query->get('mode') ?? null;

        $target = $request->query->get('target') ?? null;

        $type = $request->query->get('media') ?? null;

        $sort = $request->query->get('sort') ?? 'name_asc';

        $crossdomain = $request->query->get('crossdomain') ?? false;

        if (!$this->diskManager->has($filesystem)) {
            throw new BadRequestHttpException(sprintf('Filesystem "%s" not found.', $filesystem));
        }

        $disk = $this->diskManager->disk($filesystem);

        if (!$disk->filesystem()->directoryExists($path)) {
            throw new NotFoundHttpException(sprintf('Path "%s" not found.', $path));
        }

        return QueryFilterDTO::create($filesystem, $path, $mode, $target, $type, $sort, $crossdomain);
    }
}
