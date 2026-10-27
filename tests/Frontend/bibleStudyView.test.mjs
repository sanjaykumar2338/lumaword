import assert from 'node:assert/strict';
import test from 'node:test';
import { createElement } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { BibleStudyView } from '../../storage/framework/testing/frontend/components/BibleStudyView.js';

const study = {
    id: 1,
    title: 'The Shepherd Who Stays Near',
    scripture_key: 'psalm-23',
    scripture_reference: 'Psalm 23:1-6',
    translation: 'WEB',
    scripture_text: '1 The LORD is my shepherd.',
    depth: 'go_deeper',
    depth_label: 'Go Deeper',
    personal_notes: null,
    ai: { provider: 'openai', model: 'test-model', prompt_version: 'bible-study-v1' },
    generated_at: '2026-10-27T00:00:00.000Z',
    updated_at: '2026-10-27T00:00:00.000Z',
    content: {
        title: 'The Shepherd Who Stays Near',
        overview: 'God faithfully shepherds his people.',
        passage_context: 'A song of trust.',
        book_introduction: {
            authorship: 'Attributed to David.',
            audience: 'Israel in worship.',
            purpose: 'To express trust.',
            major_themes: ['Trust'],
            biblical_narrative: 'The shepherd image develops through Scripture.',
            scholarly_uncertainties: ['The exact life setting is unknown.'],
        },
        historical_context: [{ detail: 'Shepherds guided vulnerable flocks.', certainty: 'established' }],
        word_studies: [{
            term: 'shepherd', language: 'Hebrew', transliteration: 'ra‘ah', pronunciation: 'rah-AH',
            contextual_meaning: 'to tend a flock', possible_translations: ['shepherd', 'tend'],
            translation_significance: 'It describes active care.', caution: 'Context, not an isolated root, controls the meaning.',
        }],
        book_outline: [{ heading: 'Book I', range: 'Psalms 1-41', summary: 'Songs of trust and lament.', contains_passage: true }],
        literary_context: { genre: 'Poetry', structure: ['Shepherd image', 'Host image'], observations: ['The address becomes direct in danger.'] },
        themes: [{ theme: 'Presence', explanation: 'Trust rests on God being near.' }],
        cross_references: [{ reference: 'John 10:11', connection: 'Jesus uses the good shepherd image.' }],
        study_questions: ['Where does the psalm locate confidence?'],
        application: {
            practical_reflections: ['Name a place where trust is difficult.'],
            prayer_prompts: ['Ask for awareness of God’s presence.'],
            journaling_questions: ['Which image speaks to you?'],
            group_discussion_topics: ['How can a community embody care?'],
        },
    },
};

test('study view separates exact Scripture from structured commentary and certainty labels', () => {
    const markup = renderToStaticMarkup(createElement(BibleStudyView, { study }));

    assert.match(markup, /The passage/);
    assert.match(markup, /1 The LORD is my shepherd/);
    assert.match(markup, /Original-language words/);
    assert.match(markup, />established</);
    assert.match(markup, /Scholarly questions/);
    assert.match(markup, /John 10:11/);
    assert.match(markup, /Current passage/);
});
