<?php
/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @author      Jan Kozak <galvani78@gmail.com>
 */

namespace MauticPlugin\MauticMtalkzBundle\Transport;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\SmsBundle\Api\AbstractSmsApi;
use Monolog\Logger;

class MtalkzTransport extends AbstractSmsApi
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * @var string
     */
    private $api_key;

    /**
     * @var string
     */
    private $sender_id;

    /**
     * @var bool
     */
    protected $connected;

    /**
     * @param IntegrationHelper $integrationHelper
     * @param Logger            $logger
     */
    public function __construct(IntegrationHelper $integrationHelper, Logger $logger)
    {
        $this->integrationHelper = $integrationHelper;
        $this->logger = $logger;
        $this->connected = false;
    }

    /**
     * @param Lead   $contact
     * @param string $content
     *
     * @return bool|string
     */
    public function sendSms(Lead $contact, $content)
    {
        $number = $contact->getLeadPhoneNumber();
        if (empty($number)) {
            return false;
        }

        try {
            $number = substr($this->sanitizeNumber($number), 1);
        } catch (NumberParseException $e) {
            $this->logger->addInfo('Invalid number format. ', ['exception' => $e]);
            return $e->getMessage();
        }
        
        try {
            if (!$this->connected && !$this->configureConnection()) {
                throw new \Exception("Mtalkz SMS is not configured properly.");
            }

            $content = $this->sanitizeContent($content, $contact);
            if (empty($content)) {
                throw new \Exception('Message content is Empty.');
            }

            $response = $this->send($number, $content);
            $this->logger->addInfo("Mtalkz SMS request succeeded. ", ['response' => $response]);
            return true;
        } catch (\Exception $e) {
            $this->logger->addError("Mtalkz SMS request failed. ", ['exception' => $e]);
            return $e->getMessage();
        }
    }

    /**
     * @param integer   $number
     * @param string    $content
     * 
     * @return array
     * 
     * @throws \Exception
     */
    protected function send($number, $content)
    {
        $params = array(
            'apikey' => $this->api_key,
            'senderid' => $this->sender_id,
            'number' => $number,
            'message' => $content,
            'format' => 'json',
        );
        $url = 'http://msg.mtalkz.com/V2/http-api.php?' . http_build_query($params);
        $headers = ['Accept: application/json'];

        $this->logger->addInfo("Mtalkz SMS API request intiated. ", ['url' => $url]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
    
    /**
     * @param string $number
     *
     * @return string
     *
     * @throws NumberParseException
     */
    protected function sanitizeNumber($number)
    {
        $util = PhoneNumberUtil::getInstance();
        $parsed = $util->parse($number, 'IN');

        return $util->format($parsed, PhoneNumberFormat::E164);
    }

    /**
     * @return bool
     */
    protected function configureConnection()
    {
        $integration = $this->integrationHelper->getIntegrationObject('Mtalkz');
        if ($integration && $integration->getIntegrationSettings()->getIsPublished()) {
            $keys = $integration->getDecryptedApiKeys();
            if (empty($keys['api_key']) || empty($keys['sender_id'])) {
                return false;
            }
            $this->api_key = $keys['api_key'];
            $this->sender_id = $keys['sender_id'];
            $this->connected = true;
        }
        return $this->connected;
    }

    /**
     * @param string $content
     * @param Lead   $contact
     *
     * @return string
     */
    protected function sanitizeContent(string $content, Lead $contact) {
        return strtr($content, array(
            '{title}' => $contact->getTitle(),
            '{firstname}' => $contact->getFirstname(),
            '{lastname}' => $contact->getLastname(),
            '{name}' => $contact->getName(),
            '{company}' => $contact->getCompany(),
            '{email}' => $contact->getEmail(),
            '{address1}' => $contact->getAddress1(),
            '{address2}' => $contact->getAddress2(),
            '{city}' => $contact->getCity(),
            '{state}' => $contact->getState(),
            '{country}' => $contact->getCountry(),
            '{zipcode}' => $contact->getZipcode(),
            '{location}' => $contact->getLocation(),
            '{phone}' => $contact->getLeadPhoneNumber(),
        ));
    }
}
