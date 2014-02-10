<?php

namespace Message\Mothership\OrderReturn\Entity;

class OrderReturnItem
{
	public $id;

	// Related entities
	public $order;
	public $orderItem;
	public $exchangeItem;
	public $note;
	public $document;
	public $returnedStockLocation;

	public $authorship;
	public $status;
	public $reason;
	public $resolution;
	public $accepted;
	public $balance;
	public $calculatedBalance;

	public function __construct()
	{
		$this->authorship = new Authorship();
		$this->authorship->disableDelete();
	}

	public function isReceived()
	{
		return $this->status->code >= Statuses::RETURN_RECEIVED;
			   // or $this->status->code == Order\Statuses::CANCELLED;
	}

	public function isAccepted()
	{
		return $this->accepted == true;
	}

	public function isRejected()
	{
		return $this->accepted == false and $this->accepted !== null;
	}

	public function isRefundResolution()
	{
		return $this->resolution->code == 'refund';
	}

	public function isExchangeResolution()
	{
		return $this->resolution->code == 'exchange';
	}

	public function hasBalance()
	{
		return $this->balance !== null;
	}

	public function hasCalculatedBalance()
	{
		return $this->calculatedBalance != 0;
	}

	public function hasRemainingBalance()
	{
		// Don't need to check with !== here as null is also a value negative
		// value in this case.
		return $this->balance != 0;
	}

	/**
	 * If the balance is owed by the client to be paid to the customer.
	 *
	 * @return bool
	 */
	public function payeeIsCustomer()
	{
		if ($this->hasBalance()) return $this->balance < 0;
		return $this->calculatedBalance < 0;
	}

	/**
	 * If the balance is owed by the customer to be paid to the client.
	 *
	 * @return bool
	 */
	public function payeeIsClient()
	{
		if ($this->hasBalance()) return $this->balance > 0;
		return $this->calculatedBalance > 0;
	}

	public function isExchanged()
	{
		return $this->exchangeItem->status->code >= Order\Statuses::AWAITING_DISPATCH;
	}

	public function isReturnedItemProcessed()
	{
		return $this->status->code < Statuses::AWAITING_RETURN or
			   $this->status->code > Statuses::RETURN_RECEIVED;
	}
}