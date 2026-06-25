<?php

namespace Keyboardman\FilemanagerBundle\Controller;

use Keyboardman\FilemanagerBundle\Disk\DiskManager;
use League\Flysystem\FilesystemException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

class MediaController extends AbstractController
{
    public function __construct(private readonly DiskManager $diskManager)
    {
    }

    #[Route(
        '/kbd/filemanager/media/{filesystem}/{path}',
        name: 'keyboardman_filemanager_media',
        requirements: ['path' => '.+'],
        methods: ['GET', 'HEAD'],
    )]
    public function serve(string $filesystem, string $path, Request $request): Response
    {
        if (!$this->diskManager->has($filesystem)) {
            throw $this->createNotFoundException();
        }

        $normalizedPath = ltrim($path, '/');

        if ('' === $normalizedPath || str_contains($normalizedPath, '..')) {
            throw $this->createNotFoundException();
        }

        $fs = $this->diskManager->disk($filesystem)->filesystem();

        try {
            if (!$fs->fileExists($normalizedPath)) {
                throw $this->createNotFoundException();
            }

            $fileSize = $fs->fileSize($normalizedPath);
            $mimeType = $this->diskManager->resolveMimeType($filesystem, $normalizedPath);
        } catch (FilesystemException) {
            throw $this->createNotFoundException();
        }

        if ($fileSize < 0) {
            throw $this->createNotFoundException();
        }

        $range = $request->headers->get('Range');
        $start = 0;
        $end = $fileSize - 1;
        $status = Response::HTTP_OK;

        if (is_string($range) && preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
            $start = (int) $matches[1];
            $end = '' !== $matches[2] ? (int) $matches[2] : $fileSize - 1;

            if ($start > $end || $start >= $fileSize) {
                return new Response(null, Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE, [
                    'Content-Range' => sprintf('bytes */%d', $fileSize),
                ]);
            }

            $end = min($end, $fileSize - 1);
            $status = Response::HTTP_PARTIAL_CONTENT;
        }

        $length = $end - $start + 1;

        $response = new StreamedResponse(function () use ($fs, $normalizedPath, $start, $length) {
            $stream = $fs->readStream($normalizedPath);
            if (!is_resource($stream)) {
                return;
            }

            if ($start > 0) {
                fseek($stream, $start);
            }

            $remaining = $length;
            while ($remaining > 0 && !feof($stream)) {
                $chunk = fread($stream, min(8192, $remaining));
                if (false === $chunk) {
                    break;
                }

                echo $chunk;
                $remaining -= strlen($chunk);
            }

            fclose($stream);
        }, $status);

        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Accept-Ranges', 'bytes');
        $response->headers->set('Content-Length', (string) $length);

        if (Response::HTTP_PARTIAL_CONTENT === $status) {
            $response->headers->set('Content-Range', sprintf('bytes %d-%d/%d', $start, $end, $fileSize));
        }

        return $response;
    }
}
