export const usePageLinks = (currentPage: number, totalPages: number): number[] => {
  const pagesToShow = [currentPage - 1, currentPage, currentPage + 1].filter((page) => page > 0 && page <= totalPages);

  if (currentPage === 1 && totalPages > 2) {
    return [1, 2, 3];
  }

  if (currentPage === totalPages && totalPages > 2) {
    return [totalPages - 2, totalPages - 1, totalPages];
  }

  return [...new Set(pagesToShow)];
};
