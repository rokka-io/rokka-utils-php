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
                $until = (new \DateTime())->setTimestamp((int) ceil($until->getTimestamp() / $roundDateUpTo) * $roundDateUpTo)->setTimezone(new \DateTimeZone("UTC"));
            }
            $options = ['until' => $until->format('c')];
        }

        return self::signUrlWithOptions($url, $signKey, $options);
    }

    /**
     * Signs a rokka URL with a sign key and optional signature options.
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

        $sigOptsJson = null;
        if (null !== $options) {
            $sigOptsJson = json_encode($options);
            if (false === $sigOptsJson) {
                throw new \Exception('Could not encode options input');
            }
        }

        if (null !== $sigOptsJson) {
            // append sigopts to url
            $url = Uri::withQueryValue($url, 'sigopts', urlencode($sigOptsJson));
        } else {
            // else remove, if exists
            $url = Uri::withoutQueryValue($url, 'sigopts');
        }
        $signature = self::getSignature($url, $signKey);
        return Uri::withQueryValue($url, 'sig', $signature);
    }

    /**
     * Gets signature for an Uri
     *
     * @param \Psr\Http\Message\UriInterface $url
     * @param string $signKey
     *
     * @return string
     */
    public static function getSignature(UriInterface $url, string $signKey): string
    {
        // remove sig  if it exists
        $url = Uri::withoutQueryValue($url, 'sig');

        $query = $url->getQuery();
        $urlPath = $url->getPath() . ($query ? '?'.$query : '');

        if ('/' !== substr($urlPath, 0, 1)) {
            $urlPath = '/'.$urlPath;
        }

        $sigString = $urlPath . ':' . $signKey;
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
