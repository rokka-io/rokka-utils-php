<?php


namespace Rokka\Utils;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class SignUrl
{
    /**
     * Signs a Rokka URL with an option valid until date.
     *
     * It also rounds up the date to the next 5 minutes (300 seconds) to
     * improve CDN caching, can be changed
     *
     * @param string|UriInterface $url
     * @param string              $signKey
     * @param ?\DateTimeInterface $until         Until when is it valid
     * @param int                 $roundDateUpTo To which seconds the date should be rounded up
     *
     * @throws \Exception
     *
     * @return UriInterface
     */
    public static function signUrl($url, $signKey, $until = null, $roundDateUpTo = 300)
    {
        $options = null;

        if (null !== $until) {
            if ($roundDateUpTo > 1) {
                $until = (new \DateTime())->setTimestamp((int) ceil($until->getTimestamp() / $roundDateUpTo) * $roundDateUpTo);
            }
            $options = ['until' => $until->format('c')];
        }

        return self::signUrlWithOptions($url, $signKey, $options);
    }

    /**
     * Signs a rokka URL with a sign key and optional signature options.
     *
     * @since 1.12.0
     *
     * @param string|UriInterface $url
     * @param string              $signKey
     * @param array|null          $options
     *
     * @throws \Exception
     *
     * @return UriInterface
     */
    public static function signUrlWithOptions($url, $signKey, $options = null)
    {
        if (\is_string($url)) {
            $url = new Uri($url);
        }

        $sigOptsBase64 = null;
        if (null !== $options) {
            $json = json_encode($options);
            if (false !== $json) {
                $sigOptsBase64 = base64_encode($json);
            } else {
                throw new \Exception('Could not encode options input');
            }
        }

        $signature = self::getSignature($url, $sigOptsBase64, $signKey);
        if (null !== $sigOptsBase64) {
            // append sigopts to return url
            $url = Uri::withQueryValue($url, 'sigopts', urlencode($sigOptsBase64));
        } else {
            // else remove, if exists
            $url = Uri::withoutQueryValue($url, 'sigopts');
        }
        return Uri::withQueryValue($url, 'sig', $signature);
    }

    /**
     * @param \Psr\Http\Message\UriInterface $url
     * @param string|null $optionsBase64
     * @param string $signKey
     *
     * @return string
     */
    public static function getSignature(UriInterface $url, string $optionsBase64 = null, string $signKey): string
    {
        // remove sig and sigopts, if they exist
        $url = Uri::withoutQueryValue($url, 'sig');
        $url = Uri::withoutQueryValue($url, 'sigopts');

        $query = $url->getQuery();
        $urlPath = $url->getPath() . ($query ? '?'.$query : '');

        if ('/' !== substr($urlPath, 0, 1)) {
            $urlPath = '/'.$urlPath;
        }

        $sigString = $urlPath . ':' . ($optionsBase64 ?? '') . ':' . $signKey;
        return self::calculateSignature($sigString);
    }

    /**
     * @param string $sigString
     *
     * @return string
     */
    private static function calculateSignature(string $sigString): string
    {
        return urlencode(substr(hash('sha256', $sigString), 0, 16));
    }
}
