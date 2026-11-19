<?php

namespace App\Livewire\Public;

use App\Models\Category;
use App\Models\Module;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TrainingCatalogue extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $category = '';

    #[Url(except: 'all')]
    public string $availability = 'all';

    #[Url(except: 'newest')]
    public string $sort = 'newest';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function updatingAvailability(): void
    {
        $this->resetPage();
    }

    public function updatingSort(): void
    {
        $this->resetPage();
    }

    public function setCategory(string $slug): void
    {
        $this->category = $slug;
        $this->resetPage();
    }

    public function setAvailability(string $availability): void
    {
        if (! in_array($availability, ['all', 'independent', 'session_only'], true)) {
            return;
        }

        $this->availability = $availability;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'category', 'availability', 'sort']);
        $this->resetPage();
    }

    public function render(): View
    {
        $categories = Category::query()
            ->active()
            ->ordered()
            ->withCount('publishedModules')
            ->get();

        $query = Module::query()
            ->published()
            ->with([
                'category',
                'upcomingSessions' => fn ($q) => $q->orderBy('starts_at'),
            ])
            ->withCount(['publishedMaterials', 'upcomingSessions']);

        if (filled($this->search)) {
            $term = '%'.trim($this->search).'%';
            $query->where(function (Builder $q) use ($term): void {
                $q->where('code', 'like', $term)
                    ->orWhere('title', 'like', $term)
                    ->orWhere('summary', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        if (filled($this->category)) {
            $query->whereHas('category', fn (Builder $q) => $q->where('slug', $this->category)->where('is_active', true));
        }

        if ($this->availability === 'independent') {
            $query->where('is_available_independently', true);
        } elseif ($this->availability === 'session_only') {
            $query->where('is_available_independently', false);
        }

        match ($this->sort) {
            'title_asc' => $query->orderBy('title'),
            'price_asc' => $query->orderByRaw('CASE WHEN independent_price IS NULL THEN 1 ELSE 0 END, independent_price ASC'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };

        return view('livewire.public.training-catalogue', [
            'categories' => $categories,
            'modules' => $query->paginate(9),
        ]);
    }
}
