@if (isset($totals['valor_boleto']) || isset($totals['valor_recebido']))
    <tr class="power-grid-footer">
        <td colspan="4"></td>
        <td><b>{{ number_format($totals['valor_boleto'], 2, ',', '.') }}</b></td>
        <td></td>
        <td><b>{{ number_format($totals['valor_recebido'], 2, ',', '.') }}</b></td>
        <td colspan="3"></td>
    </tr>
@endif
