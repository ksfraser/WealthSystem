<?php
// src/Services/Calculators/TALibCalculators.php
// TA-Lib Calculator wrapper for technical indicators using lupecode/php-trader-native

namespace Services\Calculators;

use LupeCode\phpTraderNative\Trader;

class TALibCalculators
{
	/**
	 * Calculate common technical indicators for a stock's OHLCV data.
	 *
	 * @param array $ohlcv Array of arrays: [ ['Date'=>..., 'Open'=>..., 'High'=>..., 'Low'=>..., 'Close'=>..., 'Volume'=>...], ... ]
	 * @param array $indicators List of indicators to calculate (e.g. ['rsi', 'sma', 'ema', 'macd', 'bbands'])
	 * @param array $params Optional: indicator-specific parameters (e.g. ['rsi'=>14, 'sma'=>20])
	 * @return array Associative array: [ 'indicator' => [ 'Date' => value, ... ], ... ]
	 */
	public static function calculateIndicators(array $ohlcv, array $indicators = ['rsi','sma','ema','macd','bbands'], array $params = []): array
	{
		// Prepare column arrays
		$dates = [];
		$open = [];
		$high = [];
		$low = [];
		$close = [];
		$volume = [];
		foreach ($ohlcv as $row) {
			$dates[] = $row['Date'];
			$open[] = $row['Open'];
			$high[] = $row['High'];
			$low[] = $row['Low'];
			$close[] = $row['Close'];
			$volume[] = $row['Volume'];
		}

		$results = [];

		// RSI
		if (in_array('rsi', $indicators)) {
			$period = $params['rsi'] ?? 14;
			$rsi = Trader::rsi($close, $period);
			$results['rsi'] = self::alignToDates($rsi, $dates);
		}

		// SMA
		if (in_array('sma', $indicators)) {
			$period = $params['sma'] ?? 20;
			$sma = Trader::sma($close, $period);
			$results['sma'] = self::alignToDates($sma, $dates);
		}

		// EMA
		if (in_array('ema', $indicators)) {
			$period = $params['ema'] ?? 20;
			$ema = Trader::ema($close, $period);
			$results['ema'] = self::alignToDates($ema, $dates);
		}

		// MACD
		if (in_array('macd', $indicators)) {
			$fast = $params['macd_fast'] ?? 12;
			$slow = $params['macd_slow'] ?? 26;
			$signal = $params['macd_signal'] ?? 9;
			$macd = Trader::macd($close, $fast, $slow, $signal);
			$results['macd'] = [
				'macd' => self::alignToDates($macd['MACD'] ?? [], $dates),
				'signal' => self::alignToDates($macd['Signal'] ?? [], $dates),
				'hist' => self::alignToDates($macd['Hist'] ?? [], $dates),
			];
		}

		// Bollinger Bands
		if (in_array('bbands', $indicators)) {
			$period = $params['bbands'] ?? 20;
			$devUp = $params['bbands_devup'] ?? 2.0;
			$devDn = $params['bbands_devdown'] ?? 2.0;
			$bb = Trader::bbands($close, $period, $devUp, $devDn);
			$results['bbands'] = [
				'upper' => self::alignToDates($bb['UpperBand'], $dates),
				'middle' => self::alignToDates($bb['MiddleBand'], $dates),
				'lower' => self::alignToDates($bb['LowerBand'], $dates),
			];
		}

		// Add more indicators as needed...

		return $results;
	}

	/**
	 * Aligns indicator output (which may have missing leading values) to the original date array.
	 * Missing values are set to null.
	 *
	 * @param array $indicatorOut Output from Trader::* (keyed by index)
	 * @param array $dates Array of all dates (in order)
	 * @return array [ 'Date' => value, ... ]
	 */
	private static function alignToDates(array $indicatorOut, array $dates): array
	{
		$result = [];
		foreach ($dates as $i => $date) {
			$result[$date] = $indicatorOut[$i] ?? null;
		}
		return $result;
	}
}
