import { FunctionComponent, SVGProps } from 'react';

export interface EmptyTableType {
  icon?: FunctionComponent<SVGProps<SVGSVGElement>>;
  title: string;
  description: string;
}
