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
            ->add('linha')
            ->add('nosso_numero')
//            ->add('numero_boleto')
            ->add('formattedDataVencimento')
            ->add('formattedDataLiquidacao')
            ->add('comando_2', fn($row) => $row->comando . ' - ' . $row->comando_descricao)
            ->add('natureza_recebimento_2', fn($row) => $row->natureza_recebimento . ' - ' . $row->natureza_recebimento_descricao)
            ->add('canal_pagamento_2', fn($row) => $row->canal_pagamento . ' - ' . $row->canal_pagamento_descricao)
            ->add('valor_boleto', fn($row) =>
                [
                    'tpl-valor-boleto' => [
                        'valor_boleto' => 'R$ ' . number_format($row->valor_boleto, 2, ',', '.')
                    ],
                ]
            )
            ->add('valor_recebido', fn($row) =>
                [
                    'tpl-valor-recebido' => [
                        'valor_recebido' => 'R$ ' . number_format($row->valor_recebido, 2, ',', '.')
                    ],
                ]
            )
            ->add('valor_tarifa', fn($row) =>
                [
                    'tpl-valor-tarifa' => [
                        'valor_tarifa' => 'R$ ' . number_format($row->valor_tarifa, 2, ',', '.')
                    ],
                ]
            );
    }

    public function columns(): array
    {
        return [
            Column::make('Linha', 'linha')
                ->sortable()
                ->searchable(),

            Column::make('Nosso numero', 'nosso_numero')
                ->sortable()
                ->searchable(),

//            Column::make('Numero boleto', 'numero_boleto')
//                ->sortable()
//                ->searchable(),

            Column::make('Data vencimento', 'formattedDataVencimento')
                ->sortable()
                ->searchable(),

            Column::make('Data liquidacao', 'formattedDataLiquidacao')
                ->sortable()
                ->searchable(),

            Column::make('Natureza recebimento', 'natureza_recebimento_2')
                ->sortable()
                ->searchable(),

            Column::make('Canal pagamento', 'canal_pagamento_2')
                ->sortable()
                ->searchable(),

            Column::make('Comando', 'comando_2')
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

            Column::make('Valor Tarifa', 'valor_tarifa')
                ->withSum('Total: ', header: false, footer: true)
                ->sortable()
                ->searchable(),
        ];
    }

    public function filters(): array
    {
        return [
//            Filter::inputText('linha')->operators(['contains']),
            Filter::inputText('nosso_numero')->operators(['contains']),
//            Filter::inputText('numero_boleto')->operators(['contains']),
            Filter::datepicker('formattedDataVencimento'),
            Filter::datepicker('formattedDataLiquidacao'),
            Filter::select('comando_2', 'comando')
                ->dataSource(CnabRecord::select('comando_descricao as comando_2', 'comando')
                    ->distinct()
                    ->orderBy('comando')
                    ->get())
                ->optionLabel('comando_2')
                ->optionValue('comando'),
            Filter::select('natureza_recebimento_2', 'natureza_recebimento')
                ->dataSource(CnabRecord::select('natureza_recebimento_descricao as natureza_recebimento_2', 'natureza_recebimento')
                    ->distinct()
                    ->orderBy('natureza_recebimento')
                    ->get())
                ->optionLabel('natureza_recebimento_2')
                ->optionValue('natureza_recebimento'),
            Filter::select('canal_pagamento_2', 'canal_pagamento')
                ->dataSource(CnabRecord::select('canal_pagamento_descricao as canal_pagamento_2', 'canal_pagamento')
                    ->distinct()
                    ->orderBy('canal_pagamento')
                    ->get())
                ->optionLabel('canal_pagamento_2')
                ->optionValue('canal_pagamento'),
            Filter::inputText('valor_boleto')->operators(['contains']),
            Filter::inputText('valor_recebido')->operators(['contains']),
            Filter::inputText('valor_tarifa')->operators(['contains']),
        ];
    }

    public function summarizeFormat(): array
    {
        return [
            'valor_boleto.{sum}' => fn ($value) =>  ' R$ ' . number_format($value, 2, ',', '.'),
            'valor_recebido.{sum}' => fn ($value) => 'R$ ' . number_format($value, 2, ',', '.'),
            'valor_tarifa.{sum}' => fn ($value) => 'R$ ' . number_format($value, 2, ',', '.'),
        ];
    }

    public function rowTemplates(): array
    {
        return [
            'tpl-valor-boleto' => '<div class="text-right bg-gray-100 py-1 px-2">{{ valor_boleto }}</div>',
            'tpl-valor-recebido' => '<div class="text-right bg-gray-100 py-1 px-2">{{ valor_recebido }}</div>',
            'tpl-valor-tarifa' => '<div class="text-right bg-gray-100 py-1 px-2">{{ valor_tarifa }}</div>',
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
