<?php

namespace App\Livewire;

use App\Models\CnabRecord;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class RecordsTable extends PowerGridComponent
{
    public string $tableName = 'records-table-lrivyh-table';

//    public function boot(): void
//    {
//        config(['livewire-powergrid.filter' => 'outside']);
//    }

    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
            PowerGrid::detail()
                ->view('components.detail')
                ->showCollapseIcon()
                ->params(['raw_data' => 'raw_data']),

        ];
    }

    public function datasource(): Builder
    {
        return CnabRecord::query();
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('nosso_numero')
            ->add('numero_boleto')
            ->add('data_vencimento')
            ->add('valor_boleto')
            ->add('data_liquidacao')
            ->add('valor_recebido')
            ->add('comando')
            ->add('natureza_recebimento')
            ->add('canal_pagamento');
    }

    public function columns(): array
    {
        return [
            Column::make('Nosso numero', 'nosso_numero')
                ->sortable()
                ->searchable(),

            Column::make('Numero boleto', 'numero_boleto')
                ->sortable()
                ->searchable(),

            Column::make('Data vencimento', 'data_vencimento')
                ->sortable()
                ->searchable(),

            Column::make('Data liquidacao', 'data_liquidacao')
                ->sortable()
                ->searchable(),

            Column::make('Comando', 'comando')
                ->sortable()
                ->searchable(),

            Column::make('Natureza recebimento', 'natureza_recebimento')
                ->sortable()
                ->searchable(),

            Column::make('Canal pagamento', 'canal_pagamento')
                ->sortable()
                ->searchable(),

            Column::make('Valor boleto', 'valor_boleto')
                ->withSum('Total', header: false, footer: true)
                ->sortable()
                ->searchable(),

            Column::make('Valor recebido', 'valor_recebido')
                ->withSum('Total', header: false, footer: true)
                ->sortable()
                ->searchable(),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::inputText('nosso_numero')->operators(['contains']),
            Filter::inputText('valor_boleto')->operators(['contains']),
            Filter::inputText('numero_boleto')->operators(['contains']),
            Filter::select('comando', 'comando')
                ->dataSource(CnabRecord::select('comando')
                    ->distinct()
                    ->orderBy('comando')
                    ->get())
                ->optionLabel('comando')
                ->optionValue('comando'),
            Filter::select('natureza_recebimento', 'natureza_recebimento')
                ->dataSource(CnabRecord::select('natureza_recebimento')
                    ->distinct()
                    ->orderBy('natureza_recebimento')
                    ->get())
                ->optionLabel('natureza_recebimento')
                ->optionValue('natureza_recebimento'),
            Filter::select('canal_pagamento', 'canal_pagamento')
                ->dataSource(CnabRecord::select('canal_pagamento')
                    ->distinct()
                    ->orderBy('canal_pagamento')
                    ->get())
                ->optionLabel('canal_pagamento')
                ->optionValue('canal_pagamento'),
            Filter::datepicker('data_vencimento'),
            Filter::datepicker('data_liquidacao'),
        ];
    }

    public function summarizeFormat(): array
    {
        return [
            'valor_boleto.{sum}' => fn ($value) => 'R$ ' . number_format($value, 2, ',', '.'),
            'valor_recebido.{sum}' => fn ($value) => 'R$ ' . number_format($value, 2, ',', '.'),
        ];
    }

    /*
    public function actionRules($row): array
    {
       return [
            // Hide button edit for ID 1
            Rule::button('edit')
                ->when(fn($row) => $row->id === 1)
                ->hide(),
        ];
    }
    */
}
