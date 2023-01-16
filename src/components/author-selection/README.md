# AuthorSelection
A component for adding and removing authors selected via search.

## Usage
```jsx
<AuthorsSelection
  selectedAuthors={ selectedAuthors }
  updateAuthors={ updateAuthors }
/>
```

## Props
| name            | type     | required | description                                |
|-----------------|----------|----------|--------------------------------------------|
| authors         | array    | yes      | Array of author objects.                   |
| setAuthorsStore | function | yes      | Callback setter for authors array updates. |
