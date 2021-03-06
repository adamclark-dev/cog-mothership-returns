<?php

namespace Message\Mothership\OrderReturn\Controller\OrderReturn\Balance;

use Message\Mothership\OrderReturn\Event;
use Message\Mothership\OrderReturn\Events;

use Message\Cog\Controller\Controller;
use Message\Mothership\Commerce\Payment\MethodInterface;
use Message\Mothership\Commerce\Payable\PayableInterface;
use Message\Mothership\Ecommerce\Controller\Gateway\CompleteControllerInterface;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for completing a balance payment on a customer's exchange.
 *
 * @author Laurence Roberts <laurence@message.co.uk>
 */
class Complete extends Controller implements CompleteControllerInterface
{
	/**
	 * Complete an exchange balance payment reducing the return's remaining
	 * balance by the amount paid and appending a new payment to the
	 * related order.
	 *
	 * {@inheritDoc}
	 */
	public function success(PayableInterface $payable, $reference, MethodInterface $method)
	{
		// Get the amount before adjusting the balance to ensure we can use it
		// afterwards
		$amount = $payable->getPayableAmount();

		$this->get('return.edit')->addPayment($payable, $method, $amount, $reference);

		// Generate the successful url
		$salt = $this->get('cfg')->payment->salt;
		$hash = $this->get('checkout.hash')->encrypt($payable->id, $salt);

		$successful = $this->generateUrl('ms.ecom.return.balance.successful', [
			'returnID' => $payable->id,
			'hash'     => $hash,
		], UrlGeneratorInterface::ABSOLUTE_URL);

		// Return a JSON response with the successful url
		$response = new JsonResponse;
		$response->setData([
			'url' => $successful,
		]);

		return $response;
	}

	/**
	 * {@inheritDoc}
	 */
	public function cancel(PayableInterface $payable)
	{
		return $this->failure($payable);
	}

	/**
	 * {@inheritDoc}
	 */
	public function failure(PayableInterface $payable)
	{
		$salt = $this->get('cfg')->payment->salt;
		$hash = $this->get('checkout.hash')->encrypt($payable->id, $salt);

		return $this->redirectToRoute('ms.ecom.return.balance.unsuccessful', [
			'returnID' => $payable->id,
			'hash'     => $hash,
		]);
	}

	/**
	 * Show the confirmation page for an successful balance payment.
	 *
	 * @return \Message\Cog\HTTP\Response
	 */
	public function successful($returnID, $hash)
	{
		$salt = $this->get('cfg')->payment->salt;
		$checkHash = $this->get('checkout.hash')->encrypt($returnID, $salt);

		if ($hash != $checkHash) {
			throw $this->createNotFoundException();
		}

		$return = $this->get('return.loader')->getByID($returnID);

		$event = new Event($return);
		$this->get('event.dispatcher')->dispatch(Events::PAYMENT_SUCCESS, $event);

		return $this->render('Message:Mothership:OrderReturn::return:balance:success', [
			'return' => $return
		]);
	}

	/**
	 * Show the error page for an unsuccessful balance payment.
	 *
	 * @return \Message\Cog\HTTP\Response
	 */
	public function unsuccessful($returnID, $hash)
	{
		$salt = $this->get('cfg')->payment->salt;
		$checkHash = $this->get('checkout.hash')->encrypt($returnID, $salt);

		if ($hash != $checkHash) {
			throw $this->createNotFoundException();
		}

		return $this->render('Message:Mothership:OrderReturn::return:balance:error');
	}
}