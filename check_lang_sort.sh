#!/bin/bash
# Script to check if language strings in a PHP file are sorted alphabetically

if [ -z "$1" ]; then
    echo "Usage: $0 <path-to-lang-file.php>"
    exit 1
fi

FILE="$1"

if [ ! -f "$FILE" ]; then
    echo "Error: File $FILE not found"
    exit 1
fi

echo "Checking language string sorting in: $FILE"
echo ""

# Extract string keys (lines starting with $string[')
# Sort them and compare with original order
ORIGINAL=$(grep "^\$string\['" "$FILE" | sed "s/\$string\['//" | sed "s/'\].*//" )
SORTED=$(echo "$ORIGINAL" | sort)

if [ "$ORIGINAL" = "$SORTED" ]; then
    echo "✅ Language strings are sorted alphabetically!"
    exit 0
else
    echo "❌ Language strings are NOT sorted alphabetically!"
    echo ""
    echo "Expected order:"
    echo "$SORTED"
    echo ""
    echo "Current order:"
    echo "$ORIGINAL"
    exit 1
fi
