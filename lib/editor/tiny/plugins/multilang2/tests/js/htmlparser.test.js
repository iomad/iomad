import {expect, test} from '@jest/globals';
import {parseEditorContent} from './src/htmlparser.mjs';

test('Language markers spread via several block elements.', () => {
    const html = '<div class="clo">{mlang de}</div><div>Foo bar {mlang}</div>';
    const parsed = '<div class="clo"><span contenteditable="false" class="multilang-begin mceNonEditable" '
        + 'data-mce-contenteditable="false" lang="de" xml:lang="de">{mlang de}</span>'
        + '</div><div>Foo bar <span contenteditable="false" class="multilang-end mceNonEditable" '
        + 'data-mce-contenteditable="false">{mlang}</span></div>';
    expect(parseEditorContent(html)).toEqual(parsed);
});

test('Linebreak tag in between language markers.', () => {
    const html = '{mlang de}<br>{mlang}';
    const parsed = '<span contenteditable="false" class="multilang-begin mceNonEditable" '
        + 'data-mce-contenteditable="false" lang="de" xml:lang="de">{mlang de}</span><br>'
        + '<span contenteditable="false" class="multilang-end mceNonEditable" data-mce-contenteditable="false">{mlang}</span>';
    expect(parseEditorContent(html)).toEqual(parsed);
});

test('Image tag enclosed by language markers.', () => {
    const html = '{mlang de}<img src="https://example.com/image.jpg">{mlang}';
    const parsed = '<span contenteditable="false" class="multilang-begin mceNonEditable" '
        + 'data-mce-contenteditable="false" lang="de" xml:lang="de">{mlang de}</span>'
        + '<img src="https://example.com/image.jpg">'
        + '<span contenteditable="false" class="multilang-end mceNonEditable" data-mce-contenteditable="false">{mlang}</span>';
    expect(parseEditorContent(html)).toEqual(parsed);
});

test('Only one closing language tag.', () => {
    const html = '<div>Foo bar {mlang}</div>';
    expect(parseEditorContent(html)).toEqual(html);
});

test('Only one opening language tag.', () => {
    const html = '<div>{mlang de}Foo bar</div>';
    const parsed = '<div><span contenteditable="false" class="multilang-begin mceNonEditable" '
        + 'data-mce-contenteditable="false" lang="de" xml:lang="de">{mlang de}</span>Foo bar</div>';
    expect(parseEditorContent(html)).toEqual(parsed);
});

test('Enclosed language tags in between.', () => {
    const html = '<div>{mlang de}Foo bar{mlang other}Bar baz{mlang}{mlang}</div>';
    const parsed = '<div><span contenteditable="false" class="multilang-begin mceNonEditable" '
        + 'data-mce-contenteditable="false" lang="de" xml:lang="de">{mlang de}</span>Foo bar'
        + '{mlang other}Bar baz{mlang}'
        + '<span contenteditable="false" class="multilang-end mceNonEditable" data-mce-contenteditable="false">{mlang}</span>'
        + '</div>';
    expect(parseEditorContent(html)).toEqual(parsed);
});

test('Language tags in text and attribures, attributes without value.', () => {
    const html = '<p>{mlang en}This is a test{mlang}{mlang de}Das ist ein Test{mlang}.</p>\n'
        + '<p>This is a multilang link: <a\n'
        + '    href="https://google.com?lang={mlang de}de-DE{mlang}{mlang en}en-EN{mlang}" target="_blank">{mlang\n'
        + '    de}de{mlang}{mlang en}other{mlang}</a></p>\n'
        + '<p>{mlang de}</p>\n'
        + '<p>ein Paragraf auf Deutch</p>\n'
        + '<br><hr strong/>\n'
        + '<p class="clo">{mlang}</p>\n';
    const parsed = '<p><span contenteditable="false" class="multilang-begin mceNonEditable" data-mce-contenteditable="false" '
        + 'lang="en" xml:lang="en">{mlang en}</span>This is a test<span contenteditable="false" class="multilang-end mceNonEditable" '
        + 'data-mce-contenteditable="false">{mlang}</span><span contenteditable="false" class="multilang-begin mceNonEditable" '
        + 'data-mce-contenteditable="false" lang="de" xml:lang="de">{mlang de}</span>Das ist ein Test<span contenteditable="false" '
        + 'class="multilang-end mceNonEditable" data-mce-contenteditable="false">{mlang}</span>.</p>\n'
        + '<p>This is a multilang link: <a\n'
        + '    href="https://google.com?lang={mlang de}de-DE{mlang}{mlang en}en-EN{mlang}" target="_blank">'
        + '<span contenteditable="false" class="multilang-begin mceNonEditable" data-mce-contenteditable="false" '
        + 'lang="de" xml:lang="de">{mlang de}</span>de<span contenteditable="false" class="multilang-end mceNonEditable" '
        + 'data-mce-contenteditable="false">{mlang}</span><span contenteditable="false" class="multilang-begin mceNonEditable" '
        + 'data-mce-contenteditable="false" lang="en" xml:lang="en">{mlang en}</span>other<span contenteditable="false" '
        + 'class="multilang-end mceNonEditable" data-mce-contenteditable="false">{mlang}</span></a></p>\n'
        + '<p><span contenteditable="false" class="multilang-begin mceNonEditable" data-mce-contenteditable="false" lang="de" '
        + 'xml:lang="de">{mlang de}</span></p>\n'
        + '<p>ein Paragraf auf Deutch</p>\n'
        + '<br><hr strong/>\n'
        + '<p class="clo"><span contenteditable="false" class="multilang-end mceNonEditable" data-mce-contenteditable="false">'
        + '{mlang}</span></p>\n';
    expect(parseEditorContent(html)).toEqual(parsed);
});

test('Language tag mixed with already translated spans.', () => {
    const html = '<p>{mlang other}This is <b>a</b> test{mlang}{mlang de}Das ist ein Test{mlang}.</p>\n'
        + '<p><span contenteditable="false" class="multilang-begin mceNonEditable"\n'
        + 'data-mce-contenteditable="false" lang="en" xml:lang="en">{mlang en}</span>English rules'
        + '<span contenteditable="false" class="multilang-end mceNonEditable" data-mce-contenteditable="false">{mlang}</span></p>';
    const parsed = '<p><span contenteditable="false" class="multilang-begin mceNonEditable" data-mce-contenteditable="false" '
        + 'lang="other" xml:lang="other">{mlang other}</span>This is <b>a</b> test'
        + '<span contenteditable="false" class="multilang-end mceNonEditable" data-mce-contenteditable="false">{mlang}</span>'
        + '<span contenteditable="false" class="multilang-begin mceNonEditable" data-mce-contenteditable="false" '
        + 'lang="de" xml:lang="de">{mlang de}</span>Das ist ein Test'
        + '<span contenteditable="false" class="multilang-end mceNonEditable" data-mce-contenteditable="false">{mlang}</span>.</p>\n'
        + '<p><span contenteditable="false" class="multilang-begin mceNonEditable"\n'
        + 'data-mce-contenteditable="false" lang="en" xml:lang="en">{mlang en}</span>English rules'
        + '<span contenteditable="false" class="multilang-end mceNonEditable" data-mce-contenteditable="false">{mlang}</span></p>';
    expect(parseEditorContent(html)).toEqual(parsed);
});

test('Already containing tiny tags for language markers.', () => {
    const html = '<p><span contenteditable="false" class="multilang-begin mceNonEditable"\n'
        + 'data-mce-contenteditable="false" lang="en" xml:lang="en">{mlang en}</span>English rules'
        + '<span contenteditable="false" class="multilang-end mceNonEditable" data-mce-contenteditable="false">{mlang}'
        + '</span></p>';
    expect(parseEditorContent(html)).toEqual(html);
});

test('Html containing comments.', () => {
    const html = '<p><!-- {mlang de}</p>\n'
        + '<p>Hallo</p>\n'
        + '<p>{mlang}--></p>\n'
        + '<p>{mlang other}Hello{mlang}</p>\n'
        + '<p>Done</p>\n';
    const parsed = '<p><!-- {mlang de}</p>\n'
        + '<p>Hallo</p>\n'
        + '<p>{mlang}--></p>\n'
        + '<p><span contenteditable="false" class="multilang-begin mceNonEditable" '
        + 'data-mce-contenteditable="false" lang="other" xml:lang="other">{mlang other}</span>Hello'
        + '<span contenteditable="false" class="multilang-end mceNonEditable" '
        + 'data-mce-contenteditable="false">{mlang}</span></p>\n'
        + '<p>Done</p>\n';
    expect(parseEditorContent(html)).toEqual(parsed);
});

test('Html containing a svg.', () => {
    const html = '<p>Resistor: <svg xmlns="http://www.w3.org/2000/svg"\n'
        + ' version="1.1" baseProfile="full"\n'
        + ' width="700px" height="400px" viewBox="0 0 700 400">\n'
        + '\n'
        + '<!-- Connectors left and right -->\n'
        + '<line x1="0" y1="200" x2="700" y2="200" stroke="black" stroke-width="20px"/>\n'
        + '<!-- The rectangle -->\n'
        + '<rect x="100" y="100" width="500" height="200" fill="white" stroke="black" stroke-width="20px"/>\n'
        + '</svg></p>';
    expect(parseEditorContent(html)).toEqual(html);
});

test('Html with tex anotations.', () => {
    const html = '<p>The Quadratic Equation: <span class="math-tex">\\(ax^2 + bx + c = 0\\)</span></p>';
    expect(parseEditorContent(html)).toEqual(html);
});

test('Html containing mathml elements.', () => {
    const html = '<p>The Quadratic Equation: <span><math xmlns="http://www.w3.org/1998/Math/MathML">'
	      + '  <mrow>\n'
		    + '    <mi>a</mi> <mo>&InvisibleTimes;</mo> <msup><mi>x</mi><mn>2</mn></msup>\n'
		    + '    <mo>+</mo><mi>b</mi><mo>&InvisibleTimes;</mo><mi>x</mi>\n'
		    + '    <mo>+</mo><mi>c</mi>\n'
	      + '  </mrow>\n'
        + '</math></span></p>';
    expect(parseEditorContent(html)).toEqual(html);
});