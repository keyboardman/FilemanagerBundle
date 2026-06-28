<?php

namespace Keyboardman\FilemanagerBundle\Media\Streaming;

use AsyncAws\S3\S3Client;
use League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter;
use League\Flysystem\PathPrefixer;

/**
 * Contexte S3 extrait d'un adaptateur AsyncAws (client, bucket, préfixe).
 */
final class AsyncAwsS3Context
{
    public function __construct(
        private readonly S3Client $client,
        private readonly string $bucket,
        private readonly PathPrefixer $prefixer,
    ) {
    }

    /** Crée un contexte à partir d'un adaptateur AsyncAws S3. */
    public static function fromAdapter(AsyncAwsS3Adapter $adapter): self
    {
        $reflection = new \ReflectionClass($adapter);

        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);

        $bucketProperty = $reflection->getProperty('bucket');
        $bucketProperty->setAccessible(true);

        $prefixerProperty = $reflection->getProperty('prefixer');
        $prefixerProperty->setAccessible(true);

        return new self(
            $clientProperty->getValue($adapter),
            $bucketProperty->getValue($adapter),
            $prefixerProperty->getValue($adapter),
        );
    }

    /** Retourne le client S3 AsyncAws. */
    public function client(): S3Client
    {
        return $this->client;
    }

    /** Retourne le nom du bucket S3. */
    public function bucket(): string
    {
        return $this->bucket;
    }

    /** Retourne la clé objet S3 complète (avec préfixe) pour un chemin relatif. */
    public function objectKey(string $path): string
    {
        return $this->prefixer->prefixPath($path);
    }
}
