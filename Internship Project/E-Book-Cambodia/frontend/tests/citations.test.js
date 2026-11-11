import assert from 'node:assert/strict'
import test from 'node:test'
import {
  countWords,
  sanitizeCitations,
  stripCitations,
  tokenizeCitations,
} from '../src/utils/citations.js'

test('turns a marker into a cite token and moves the full stop before it', () => {
  const tokens = tokenizeCitations('Water boils at 100 °C [cite: 7]. Next sentence.')

  assert.deepEqual(tokens, [
    { type: 'text', value: 'Water boils at 100 °C.' },
    { type: 'cite', ids: [7] },
    { type: 'text', value: ' Next sentence.' },
  ])
})

test('supports several references and loose spacing or casing', () => {
  const tokens = tokenizeCitations('Fact [CITE : 7,9 ; 12].')

  assert.deepEqual(tokens, [
    { type: 'text', value: 'Fact.' },
    { type: 'cite', ids: [7, 9, 12] },
  ])
})

test('leaves unrelated brackets and malformed markers alone', () => {
  const input = 'See [7] and [citation] and [cite: ] and [cite: x] here.'

  assert.equal(stripCitations(input), input)
  assert.equal(sanitizeCitations(input), input)
})

test('stripCitations removes the artifact without leaving double spaces', () => {
  assert.equal(
    stripCitations('The value [cite: 3] is exact.'),
    'The value is exact.',
  )
  assert.equal(
    stripCitations('Two refs [cite: 3, 4] here.'),
    'Two refs here.',
  )
})

test('sanitizeCitations keeps numbered markers for exports', () => {
  assert.equal(
    sanitizeCitations('Water boils [cite: 7].'),
    'Water boils.[7]',
  )
  assert.equal(
    sanitizeCitations('Both [cite: 2, 8] agree.'),
    'Both[2][8] agree.',
  )
})

test('sanitizing is idempotent', () => {
  const once = sanitizeCitations('Alpha [cite: 1]. Beta [cite: 2, 3].')
  const twice = sanitizeCitations(once)

  assert.equal(twice, once)
})

test('countWords counts visible words only', () => {
  assert.equal(countWords('One two three [cite: 9].'), 3)
  assert.equal(countWords('[cite: 1]'), 0)
})
