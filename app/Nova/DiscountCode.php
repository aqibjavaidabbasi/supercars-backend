<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Currency;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\HasMany;

class DiscountCode extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\DiscountCode>
     */
    public static $model = \App\Models\DiscountCode::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'code';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'code',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @return array<int, \Laravel\Nova\Fields\Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            Text::make('Code')
                ->rules('required', 'string', 'max:255', 'unique:discount_codes,code,{{resourceId}}')
                ->help('Enter a unique discount code (e.g., SAVE10, SUMMER2025)')
                ->sortable(),

            Select::make('Type')
                ->options([
                    'percentage' => 'Percentage',
                    'fixed' => 'Fixed Amount',
                ])
                ->rules('required')
                ->displayUsingLabels()
                ->help('Choose whether this discount is a percentage or fixed amount')
                ->sortable(),

            Number::make('Value')
                ->rules('required', 'numeric', 'min:0')
                ->help('For percentage: enter 0-100. For fixed: enter amount in £')
                ->sortable()
                ->step(0.01),

            Number::make('Max Uses', 'max_uses')
                ->nullable()
                ->rules('nullable', 'integer', 'min:1')
                ->help('Leave empty for unlimited uses')
                ->sortable(),

            Number::make('Times Used', 'times_used')
                ->exceptOnForms()
                ->sortable(),

            DateTime::make('Expires At', 'expires_at')
                ->nullable()
                ->rules('nullable', 'date', 'after:today')
                ->help('Leave empty if code never expires')
                ->sortable(),

            Boolean::make('Is Active', 'is_active')
                ->help('Toggle to enable/disable this discount code')
                ->sortable(),

            Boolean::make('One Time Per User', 'one_time_per_user')
                ->help('If enabled, each user can only use this code once')
                ->sortable(),

            Currency::make('Min Order Value', 'min_order_value')
                ->currency('GBP')
                ->nullable()
                ->rules('nullable', 'numeric', 'min:0')
                ->help('Minimum order value required to use this code')
                ->sortable()
                ->step(0.01),

            Text::make('Formatted Value', function () {
                return $this->formatted_value;
            })->onlyOnIndex(),

            Text::make('Status', function () {
                if (!$this->is_active) {
                    return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>';
                }
                if ($this->expires_at && $this->expires_at->isPast()) {
                    return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Expired</span>';
                }
                if ($this->max_uses && $this->times_used >= $this->max_uses) {
                    return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Max Uses Reached</span>';
                }
                return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>';
            })->asHtml()->onlyOnIndex(),

            HasMany::make('Orders'),
            
            BelongsToMany::make('Users')
                ->help('Users who have used this discount code'),
        ];
    }

    /**
     * Get the cards available for the resource.
     *
     * @return array<int, \Laravel\Nova\Card>
     */
    public function cards(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @return array<int, \Laravel\Nova\Filters\Filter>
     */
    public function filters(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @return array<int, \Laravel\Nova\Lenses\Lens>
     */
    public function lenses(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @return array<int, \Laravel\Nova\Actions\Action>
     */
    public function actions(NovaRequest $request): array
    {
        return [];
    }
}
