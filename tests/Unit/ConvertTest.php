<?php

namespace AshAllenDesign\LaravelExchangeRates\Tests\Unit;

use AshAllenDesign\LaravelExchangeRates\classes\RequestBuilder;
use AshAllenDesign\LaravelExchangeRates\exceptions\InvalidCurrencyException;
use AshAllenDesign\LaravelExchangeRates\exceptions\InvalidDateException;
use AshAllenDesign\LaravelExchangeRates\ExchangeRate;
use Illuminate\Support\Facades\Cache;
use Mockery;

class ConvertTest extends TestCase
{
    /** @test */
    public function converted_value_for_today_is_returned_if_no_date_parameter_passed_and_rate_is_not_cached()
    {
        $requestBuilderMock = Mockery::mock(RequestBuilder::class);
        $requestBuilderMock->expects('makeRequest')
            ->withArgs(['/latest', ['base' => 'EUR']])
            ->once()
            ->andReturn($this->mockResponseForCurrentDate());

        $exchangeRate = new ExchangeRate($requestBuilderMock);
        $rate = $exchangeRate->convert(100, 'EUR', 'GBP');
        $this->assertEquals('86.158', $rate);
    }

    /** @test */
    public function converted_value_in_the_past_is_returned_if_date_parameter_passed_and_rate_is_not_cached()
    {
        $mockDate = now();

        $requestBuilderMock = Mockery::mock(RequestBuilder::class);
        $requestBuilderMock->expects('makeRequest')
            ->withArgs(['/'.$mockDate->format('Y-m-d'), ['base' => 'EUR']])
            ->once()
            ->andReturn($this->mockResponseForPastDate());

        $exchangeRate = new ExchangeRate($requestBuilderMock);
        $rate = $exchangeRate->convert(100, 'EUR', 'GBP', $mockDate);
        $this->assertEquals('87.053', $rate);
        $this->assertEquals('0.87053', Cache::get('EUR_GBP_'.$mockDate->format('Y-m-d')));
    }

    /** @test */
    public function cached_exchange_rate_is_used_if_it_exists()
    {
        $mockDate = now();

        Cache::forever('EUR_GBP_'.$mockDate->format('Y-m-d'), '0.123456');

        $requestBuilderMock = Mockery::mock(RequestBuilder::class);
        $requestBuilderMock->expects('makeRequest')->never();

        $exchangeRate = new ExchangeRate($requestBuilderMock);
        $rate = $exchangeRate->convert(100, 'EUR', 'GBP', $mockDate);
        $this->assertEquals('12.3456', $rate);
        $this->assertEquals('0.123456', Cache::get('EUR_GBP_'.$mockDate->format('Y-m-d')));
    }

    /** @test */
    public function cached_exchange_rate_is_not_used_if_should_bust_cache_method_is_called()
    {
        $mockDate = now();

        Cache::forever('EUR_GBP_'.$mockDate->format('Y-m-d'), '0.123456');

        $requestBuilderMock = Mockery::mock(RequestBuilder::class);
        $requestBuilderMock->expects('makeRequest')
            ->withArgs(['/'.$mockDate->format('Y-m-d'), ['base' => 'EUR']])
            ->once()
            ->andReturn($this->mockResponseForPastDate());

        $exchangeRate = new ExchangeRate($requestBuilderMock);
        $rate = $exchangeRate->shouldBustCache()->convert(100, 'EUR', 'GBP', $mockDate);
        $this->assertEquals('87.053', $rate);
        $this->assertEquals('0.87053', Cache::get('EUR_GBP_'.$mockDate->format('Y-m-d')));
    }

    /** @test */
    public function exception_is_thrown_if_the_date_parameter_passed_is_in_the_future()
    {
        $this->expectException(InvalidDateException::class);
        $this->expectExceptionMessage('The date must be in the past.');

        $exchangeRate = new ExchangeRate();
        $exchangeRate->convert(100, 'EUR', 'GBP', now()->addMinute());
    }

    /** @test */
    public function exception_is_thrown_if_the_from_parameter_is_invalid()
    {
        $this->expectException(InvalidCurrencyException::class);
        $this->expectExceptionMessage('INVALID is not a valid country code.');

        $exchangeRate = new ExchangeRate();
        $exchangeRate->convert(100, 'INVALID', 'GBP', now()->subMinute());
    }

    /** @test */
    public function exception_is_thrown_if_the_to_parameter_is_invalid()
    {
        $this->expectException(InvalidCurrencyException::class);
        $this->expectExceptionMessage('INVALID is not a valid country code.');

        $exchangeRate = new ExchangeRate();
        $exchangeRate->convert(100, 'GBP', 'INVALID', now()->subMinute());
    }

    private function mockResponseForCurrentDate()
    {
        return [
            'rates' => [
                'CAD' => 1.4561,
                'HKD' => 8.6372,
                'ISK' => 137.7,
                'PHP' => 55.809,
                'DKK' => 7.4727,
                'HUF' => 333.37,
                'CZK' => 25.486,
                'AUD' => 1.6065,
                'RON' => 4.7638,
                'SEK' => 10.7025,
                'IDR' => 15463.05,
                'INR' => 78.652,
                'BRL' => 4.5583,
                'RUB' => 70.4653,
                'HRK' => 7.4345,
                'JPY' => 120.72,
                'THB' => 33.527,
                'CHF' => 1.0991,
                'SGD' => 1.5002,
                'PLN' => 4.261,
                'BGN' => 1.9558,
                'TRY' => 6.3513,
                'CNY' => 7.7115,
                'NOK' => 10.0893,
                'NZD' => 1.7426,
                'ZAR' => 16.3121,
                'USD' => 1.1034,
                'MXN' => 21.1383,
                'ILS' => 3.8533,
                'GBP' => 0.86158,
                'KRW' => 1276.66,
                'MYR' => 4.5609,
            ],
            'base'  => 'EUR',
            'date'  => '2019-11-08',
        ];
    }

    private function mockResponseForPastDate()
    {
        return
            [
                'rates' => [
                    'CAD' => 1.4969,
                    'HKD' => 8.8843,
                    'ISK' => 138.5,
                    'PHP' => 60.256,
                    'DKK' => 7.4594,
                    'HUF' => 321.31,
                    'CZK' => 25.936,
                    'AUD' => 1.5663,
                    'RON' => 4.657,
                    'SEK' => 10.2648,
                    'IDR' => 16661.6,
                    'INR' => 82.264,
                    'BRL' => 4.254,
                    'RUB' => 76.4283,
                    'HRK' => 7.43,
                    'JPY' => 129.26,
                    'THB' => 37.453,
                    'CHF' => 1.1414,
                    'SGD' => 1.5627,
                    'PLN' => 4.288,
                    'BGN' => 1.9558,
                    'TRY' => 6.2261,
                    'CNY' => 7.8852,
                    'NOK' => 9.5418,
                    'NZD' => 1.6815,
                    'ZAR' => 16.1884,
                    'USD' => 1.1346,
                    'MXN' => 23.0001,
                    'ILS' => 4.171,
                    'GBP' => 0.87053,
                    'KRW' => 1278.77,
                    'MYR' => 4.7399,
                ],
                'base'  => 'EUR',
                'date'  => '2018-11-09',
            ];
    }
}
