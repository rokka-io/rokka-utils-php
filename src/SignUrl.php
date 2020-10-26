<?php


namespace Rokka\Utils;


use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class SignUrl {
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
     * @return string
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

        if (null !== $options) {
            $json = json_encode(json_encode($options));
            if (false !== $json) {
                $options = base64_encode($json);
            } else {
                throw new \Exception('Could not encode options input');
            }
        }
        $urlPath = $url->getPath();
        // remove sig and sigopts values, if they exists for some reason
        $url = Uri::withoutQueryValue($url, 'sig');
        $url = Uri::withoutQueryValue($url, 'sigopts');
        $urlQuery = $url->getQuery();
        // add query string
        if ($urlQuery) {
            $urlPath .= '?'.$urlQuery;
        }
        // if urlPath doesn't start with a /, add one to be sure it's there
        if ('/' !== substr($urlPath, 0, 1)) {
            $urlPath = '/'.$urlPath;
        }
        $sigString = $urlPath.':'.($options ?? '').':'.$signKey;

        if (null !== $options) {
            $url = Uri::withQueryValue($url, 'sigopts', urlencode($options));
        }

        return Uri::withQueryValue($url, 'sig', urlencode(substr(hash('sha256', $sigString), 0, 16)));
    }
}