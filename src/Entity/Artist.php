<?php

namespace App\Entity;

use App\Repository\ArtistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArtistRepository::class)]
class Artist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $artistSlug = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $albumCount = null;

    #[ORM\Column(length: 1024, nullable: true)]
    private ?string $coveArtPath = null;

    #[ORM\ManyToMany(targetEntity: Album::class, mappedBy: 'artists')]
    private Collection $albums;

    public function __construct()
    {
        $this->albums = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getArtistSlug(): ?string
    {
        return $this->artistSlug;
    }

    public function setArtistSlug(string $artistSlug): self
    {
        $this->artistSlug = $artistSlug;

        return $this;
    }

    public function getAlbumCount(): ?int
    {
        return $this->albumCount;
    }

    public function setAlbumCount(int $albumCount): self
    {
        $this->albumCount = $albumCount;

        return $this;
    }

    public function getCoveArtPath(): ?string
    {
        return $this->coveArtPath;
    }

    public function setCoveArtPath(?string $coveArtPath): self
    {
        $this->coveArtPath = $coveArtPath;

        return $this;
    }

    /**
     * @return Collection<int, Album>
     */
    public function getAlbums(): Collection
    {
        return $this->albums;
    }

    public function addAlbum(Album $album): self
    {
        if (!$this->albums->contains($album)) {
            $this->albums->add($album);
            $album->addArtist($this);
        }

        return $this;
    }

    public function removeAlbum(Album $album): self
    {
        if ($this->albums->removeElement($album)) {
            $album->removeArtist($this);
        }

        return $this;
    }
}
