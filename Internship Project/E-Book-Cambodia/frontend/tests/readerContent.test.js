import assert from 'node:assert/strict'
import test from 'node:test'
import {
  extractReferences,
  parseBlocks,
  parseSections,
  searchSections,
  sectionText,
} from '../src/utils/readerContent.js'

test('splits === content into stable source sections', () => {
  const sections = parseSections('How to read\n\nIntro text.\n\n===\n\nLesson 1: Time\n\nFirst body.\n\n===\n\nLesson 2\n\nSecond body.')

  assert.equal(sections.length, 3)
  assert.deepEqual(sections.map((section) => section.page), [1, 2, 3])
  assert.equal(sections[0].title, 'How to read')
  assert.equal(sections[1].title, 'Lesson 1: Time')
  assert.equal(sectionText(sections[1]), 'First body.')
})

test('falls back to Khmer and English headings when === is missing', () => {
  const content = [
    'មេរៀនទី ១ - ការបូកលេខ',
    '',
    'Addition combines numbers.',
    '',
    'មេរៀនទី ២ - ការដកលេខ',
    '',
    'Subtraction removes numbers.',
    '',
    'Chapter 3: Multiplication',
    '',
    'Multiplication repeats addition.',
  ].join('\n')

  const sections = parseSections(content)

  assert.equal(sections.length, 3)
  assert.match(sections[0].title, /មេរៀនទី ១/)
  assert.match(sections[2].title, /Chapter 3/)
  assert.match(sectionText(sections[2]), /Multiplication repeats/)
})

test('parses paragraphs, lists, quotes and display maths', () => {
  const blocks = parseBlocks([
    '## Worked example',
    '',
    'Read the line',
    'and the next line.',
    '',
    '- first',
    '- second',
    '',
    '> A quoted idea.',
    '',
    '$$',
    'x = \\frac{1}{2}',
    '$$',
  ].join('\n'))

  assert.deepEqual(blocks[0], { type: 'heading', level: 3, text: 'Worked example' })
  assert.equal(blocks[1].text, 'Read the line and the next line.')
  assert.deepEqual(blocks[2], { type: 'ul', items: ['first', 'second'] })
  assert.deepEqual(blocks[3], { type: 'quote', text: 'A quoted idea.' })
  assert.equal(blocks[4].type, 'math')
  assert.match(blocks[4].text, /frac/)
})

test('search ignores citation artifacts and returns a readable snippet', () => {
  const sections = parseSections(
    'Lesson 1\n\nWater boils at 100 °C [cite: 4] at sea level.\n\n===\n\nLesson 2\n\nIce melts at 0 °C.',
  )
  const results = searchSections(sections, '100 °C')

  assert.equal(results.length, 1)
  assert.equal(results[0].section, 0)
  assert.match(results[0].snippet, /Water boils/)
  assert.doesNotMatch(results[0].snippet, /\[cite/)
})

test('builds a source list from numbered references', () => {
  const sections = parseSections(
    [
      'Lesson 1',
      '',
      'A claim [cite: 2].',
      '',
      '===',
      '',
      'References',
      '',
      '[1] Ministry of Education. Science textbook. 2024.',
      '[2] UNESCO. Reading in the digital age. 2023.',
      '',
      '===',
      '',
      'Appendix',
      '',
      'Extra material.',
    ].join('\n'),
  )
  const references = extractReferences(sections)

  assert.equal(references.get(1), 'Ministry of Education. Science textbook. 2024.')
  assert.equal(references.get(2), 'UNESCO. Reading in the digital age. 2023.')
})

test('reads ordered lists as references and stops at the next section', () => {
  const sections = parseSections(
    [
      'References',
      '',
      '1. First source.',
      '2. Second source.',
      '',
      '===',
      '',
      'Appendix',
      '',
      '1. This is not a reference.',
    ].join('\n'),
  )
  const references = extractReferences(sections)

  assert.equal(references.get(1), 'First source.')
  assert.equal(references.get(2), 'Second source.')
  assert.equal(references.has(3), false)
})

test('empty content produces no sections', () => {
  assert.deepEqual(parseSections(''), [])
  assert.deepEqual(parseSections('   \n\n '), [])
})
