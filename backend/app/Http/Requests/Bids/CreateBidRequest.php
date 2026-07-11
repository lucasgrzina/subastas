<?php

namespace App\Http\Requests\Bids;

use App\Models\Lot;
use App\Support\BidderVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CreateBidRequest extends FormRequest
{
    /**
     * Resolves the route-bound Lot and, for a caller holding the `bidder`
     * role, applies the same Lot/Auction visibility gate used by
     * show()/bid-history. This runs BEFORE rules(), so a hidden auction
     * returns 404 independent of the submitted amount's validity (tasks
     * obs#41 4.3, risk #1 CRITICAL). General permission enforcement
     * (`bids.create`) stays as the standard inline `can()` check in the
     * controller, which only runs after this passes.
     */
    public function authorize(): bool
    {
        $lot = $this->resolveLot();

        if (! $lot) {
            return false;
        }

        if ($this->user()?->hasRole('bidder') && ! BidderVisibility::lotVisible($lot)) {
            return false;
        }

        return true;
    }

    protected function failedAuthorization(): void
    {
        throw new NotFoundHttpException('Lote no encontrado.');
    }

    public function rules(): array
    {
        return [
            // 'string' rejects a bare JSON number before the decimal-precision
            // check runs, closing the implicit float-coercion gap (risk #4).
            'amount' => ['required', 'string', 'numeric', 'gt:0', 'decimal:0,2'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'El monto de la oferta es obligatorio.',
            'amount.string' => 'El monto de la oferta debe enviarse como texto.',
            'amount.numeric' => 'El monto de la oferta debe ser numérico.',
            'amount.gt' => 'El monto de la oferta debe ser mayor a cero.',
            'amount.decimal' => 'El monto de la oferta admite hasta 2 decimales.',
        ];
    }

    private function resolveLot(): ?Lot
    {
        $guid = $this->route('guid');

        return $guid ? Lot::with('auction')->where('guid', $guid)->first() : null;
    }
}
